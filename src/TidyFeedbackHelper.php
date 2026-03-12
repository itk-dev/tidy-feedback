<?php

declare(strict_types=1);

namespace ItkDev\TidyFeedback;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class TidyFeedbackHelper implements EventSubscriberInterface
{
    private const string CONFIG_DATABASE_URL = 'database_url';
    private const string CONFIG_DEBUG = 'debug';
    private const string CONFIG_DEV_MODE = 'dev_mode';
    private const string CONFIG_DEFAULT_LOCALE = 'default_locale';
    private const string CONFIG_DISABLE = 'disable';
    private const string CONFIG_DISABLE_PATTERN = 'disable_pattern';
    private const string CONFIG_CACHE_DIR = 'cache_dir';
    private const string CONFIG_USERS = 'users';

    private const string ASSET_PATH = __DIR__.'/../build';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function generateUrl(string $name, array $parameters = []): string
    {
        return $this->urlGenerator->generate($name, $parameters);
    }

    public function getWidget(Request $request): string
    {
        $app = [
            // @todo Get user and pass it to widget template.
            'user' => null,
            'request' => $request,
        ];

        return $this->renderTemplate('widget.html.twig', [
            'app' => $app,
            'default_values' => (array) ($request->query->all()['tidy-feedback'] ?? null),
        ]);
    }

    private static Environment $twig;

    public function renderResponse(string $path, array $context = []): Response
    {
        return new Response($this->renderTemplate($path, $context));
    }

    public function renderTemplate(string $path, array $context = []): string
    {
        if (empty(self::$twig)) {
            // https://twig.symfony.com/doc/3.x/api.html#basics
            $loader = new FilesystemLoader(__DIR__.'/../templates');
            $cacheDir = self::getConfig(self::CONFIG_CACHE_DIR);
            $options = [];
            if ($cacheDir) {
                $options['cache'] = $cacheDir.'/twig';
                $options['auto_reload'] = self::getConfig(self::CONFIG_DEBUG) || self::getConfig(self::CONFIG_DEV_MODE);
            }
            self::$twig = new Environment($loader, $options);

            self::$twig->addFilter(new TwigFilter('trans', $this->trans(...)));
            self::$twig->addFunction(new TwigFunction('path', fn (string $name, array $parameters = []) => $this->urlGenerator->generate($name, $parameters)));
        }

        $template = self::$twig->load($path);

        return $template->render($context);
    }

    private static array $translations;

    private function getTranslations(): array
    {
        if (!isset(self::$translations)) {
            $translationFiles = glob(__DIR__.'/../translations/*.yaml');
            $cacheDir = self::getConfig(self::CONFIG_CACHE_DIR);

            if ($cacheDir && !empty($translationFiles)) {
                // Note: if a translation file is deleted between glob() and filemtime(),
                // filemtime() will fail. This is unlikely outside of deployment.
                $maxMtime = max(array_map('filemtime', $translationFiles));
                $cacheKey = 'translations_'.$maxMtime;

                $cache = new FilesystemAdapter('tidy_feedback', 0, $cacheDir);
                $item = $cache->getItem($cacheKey);

                if ($item->isHit()) {
                    self::$translations = $item->get();

                    return self::$translations;
                }

                self::$translations = $this->parseTranslationFiles($translationFiles);
                $item->set(self::$translations);
                $cache->save($item);
            } else {
                self::$translations = $this->parseTranslationFiles($translationFiles ?: []);
            }
        }

        return self::$translations;
    }

    private function parseTranslationFiles(array $files): array
    {
        $translations = [];
        foreach ($files as $file) {
            if (preg_match('/\.(?P<locale>[^.]+)\.yaml$/', $file, $matches)) {
                $locale = $matches['locale'];
                try {
                    $translations[$locale] = Yaml::parseFile($file);
                } catch (\Exception) {
                }
            }
        }

        return $translations;
    }

    private function trans(string $text, array $context = []): string
    {
        $translations = $this->getTranslations();

        // @todo Get the locale from some context …
        $locale = self::getConfig(self::CONFIG_DEFAULT_LOCALE);
        $fallbackLocale = 'en';

        return $translations[$locale][$text] ?? $translations[$fallbackLocale][$text] ?? $text;
    }

    private static EntityManager $entityManager;

    /**
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/tutorials/getting-started.html#obtaining-the-entitymanager
     */
    public static function getEntityManager(): EntityManagerInterface
    {
        if (empty(self::$entityManager)) {
            $createAttributeMetadataConfigurationFunction = method_exists(ORMSetup::class, 'createAttributeMetadataConfig')
                ? 'createAttributeMetadataConfig'
                // ORMSetup::createAttributeMetadataConfiguration has been deprecated.
                : 'createAttributeMetadataConfiguration';
            /** @var Configuration $config */
            $config = ORMSetup::{$createAttributeMetadataConfigurationFunction}(
                paths: [__DIR__.'/Model'],
                isDevMode: self::getConfig(self::CONFIG_DEV_MODE),
            );
            if (method_exists($config, 'enableNativeLazyObjects')) {
                $config->enableNativeLazyObjects(true);
            }
            $dsn = self::getConfig(self::CONFIG_DATABASE_URL);
            $connectionParams = (new DsnParser())->parse($dsn);
            $connection = DriverManager::getConnection($connectionParams, $config);

            self::$entityManager = new EntityManager($connection, $config);
        }

        return self::$entityManager;
    }

    public function createAssetResponse(string $asset): Response
    {
        $buildDir = realpath(self::ASSET_PATH);
        if (false === $buildDir) {
            throw new NotFoundHttpException();
        }

        $filename = realpath(self::ASSET_PATH.'/'.$asset);
        if (false === $filename || !str_starts_with($filename, $buildDir.'/')) {
            throw new NotFoundHttpException();
        }

        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $mimeTypes = new MimeTypes();
        $types = $mimeTypes->getMimeTypes($ext);
        if (empty($types)) {
            throw new NotFoundHttpException();
        }
        $response = new BinaryFileResponse(
            $filename,
            headers: [
                'content-type' => reset($types),
            ],
            autoEtag: true, autoLastModified: true
        );

        if (self::getConfig(self::CONFIG_DEBUG)) {
            // setExpires(null) does not seem to work as advertised, so we use a date in the far past.
            $response->setExpires(new \DateTimeImmutable('2001-01-01'));
        }

        return $response;
    }

    public static function updateSchema(OutputInterface $output): bool
    {
        try {
            $entityManager = TidyFeedbackHelper::getEntityManager();
            $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();
            $schemaTool = new SchemaTool($entityManager);
            $sql = $schemaTool->getUpdateSchemaSql($metadatas);
            if (empty($sql)) {
                $output->writeln('Schema already up to date');
            }
            $output->writeln($sql);
            $schemaTool->updateSchema($metadatas);

            return true;
        } catch (\Exception $exception) {
            $output->writeln($exception->getMessage());
        }

        return false;
    }

    private static array $config;

    private static function getConfig(?string $name): mixed
    {
        if (!isset(self::$config)) {
            $getEnv = static fn (string $name) => getenv($name) ?: ($_ENV[$name] ?? null);

            self::$config = [
                self::CONFIG_CACHE_DIR => $getEnv('TIDY_FEEDBACK_CACHE_DIR') ?: null,
                // https://www.doctrine-project.org/projects/doctrine-dbal/en/4.2/reference/configuration.html#connecting-using-a-url
                self::CONFIG_DATABASE_URL => $getEnv('TIDY_FEEDBACK_DATABASE_URL'),
                self::CONFIG_DEBUG => 'true' === $getEnv('TIDY_FEEDBACK_DEBUG'),
                self::CONFIG_DEFAULT_LOCALE => $getEnv('TIDY_FEEDBACK_DEFAULT_LOCALE') ?? 'da',
                self::CONFIG_DEV_MODE => 'true' === $getEnv('TIDY_FEEDBACK_DEV_MODE'),
                self::CONFIG_DISABLE => 'true' === $getEnv('TIDY_FEEDBACK_DISABLE'),
                self::CONFIG_DISABLE_PATTERN => $getEnv('TIDY_FEEDBACK_DISABLE_PATTERN') ?? '@^/tidy-feedback$@',
                self::CONFIG_USERS => [],
            ];

            if ($users = $getEnv('TIDY_FEEDBACK_USERS')) {
                try {
                    self::$config[self::CONFIG_USERS] = json_decode($users, true, flags: JSON_THROW_ON_ERROR);
                } catch (\Throwable) {
                }
            }
        }

        return $name ? (self::$config[$name] ?? null) : self::$config;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse'],
        ];
    }

    /**
     * Kernel response event handler.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (self::getConfig(self::CONFIG_DISABLE)) {
            return;
        }

        if ($pattern = self::getConfig(self::CONFIG_DISABLE_PATTERN)) {
            $request = $event->getRequest();
            $uri = $request->getPathInfo();
            if (@preg_match($pattern, $uri)) {
                return;
            }
        }

        $response = $event->getResponse();
        if (false
          || !$response->isSuccessful()
            // This does not work as expected in Drupal!
            // || !str_starts_with((string)$response->headers->get('content-type'), 'text/html')
        ) {
            return;
        }

        try {
            $widget = $this->getWidget($event->getRequest());
            if (empty($widget)) {
                return;
            }

            if ($content = $response->getContent()) {
                $content = preg_replace('~</body>~i', $widget.'$0', (string) $content);
                $response->setContent($content);
            }
        } catch (\Throwable $throwable) {
            if (self::getConfig(self::CONFIG_DEBUG)) {
                throw $throwable;
            }
            // Ignore all errors!
        }
    }

    public function authorize(Request $request): void
    {
        $users = self::getConfig(self::CONFIG_USERS);
        if (empty($users)) {
            return;
        }

        [$user, $password] = [$request->getUser(), $request->getPassword()];
        if (empty($user) || empty($password) || $password !== ($users[$user] ?? null)) {
            throw new UnauthorizedHttpException('Basic realm="Tidy feedback"');
        }
    }
}
