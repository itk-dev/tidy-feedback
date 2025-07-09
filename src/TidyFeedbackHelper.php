<?php

declare(strict_types=1);

namespace ItkDev\TidyFeedback;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class TidyFeedbackHelper implements EventSubscriberInterface
{
    private const string ASSET_PATH = __DIR__.'/../build';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getWidget(): string
    {
        $app = [
            // @todo Get user and pass it to widget template.
            'user' => null,
        ];

        return $this->renderTemplate('widget.html.twig', [
            'app' => $app,
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
            self::$twig = new Environment($loader, [
                // @todo
                // 'cache' => '/tmp/twig_compilation_cache',
            ]);

            self::$twig->addFilter(new TwigFilter('trans', $this->trans(...)));
            self::$twig->addFunction(new TwigFunction('path', fn (string $name, array $parameters = []) => $this->urlGenerator->generate($name, $parameters)));
        }

        $template = self::$twig->load($path);

        return $template->render($context);
    }

    private static array $translations;

    private function trans(string $text, array $context = []): string
    {
        if (!isset(self::$translations)) {
            self::$translations = [];
            foreach (glob(__DIR__.'/../translations/*.yaml') as $file) {
                if (preg_match('/\.(?P<locale>[^.]+)\.yaml$/', $file, $matches)) {
                    $locale = $matches['locale'];
                    try {
                        self::$translations[$locale] = Yaml::parseFile($file);
                    } catch (\Exception) {
                    }
                }
            }
        }

        // @todo Get the locale from some context …
        $locale = self::getConfig('default_locale');
        $fallbackLocale = 'en';

        return self::$translations[$locale][$text] ?? self::$translations[$fallbackLocale][$text] ?? $text;
    }

    private static EntityManager $entityManager;

    /**
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/tutorials/getting-started.html#obtaining-the-entitymanager
     */
    public static function getEntityManager(): EntityManagerInterface
    {
        if (empty(self::$entityManager)) {
            $config = ORMSetup::createAttributeMetadataConfig(
                paths: [__DIR__.'/Model'],
                isDevMode: (bool) ($_ENV['TIDY_FEEDBACK_DEV_MODE'] ?? false),
            );
            $config->enableNativeLazyObjects(true);

            $dsn = self::getConfig('database_url');
            $connectionParams = (new DsnParser())->parse($dsn);
            $connection = DriverManager::getConnection($connectionParams, $config);

            self::$entityManager = new EntityManager($connection, $config);
        }

        return self::$entityManager;
    }

    public function createAssetResponse(string $asset): Response
    {
        $filename = self::ASSET_PATH.'/'.$asset;

        if (!is_readable($filename)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($filename, headers: [
            'content-type' => match (pathinfo($filename, PATHINFO_EXTENSION)) {
                'css' => 'text/css',
                'js' => 'text/javascript',
                default => throw new NotFoundHttpException(),
            },
        ],
            autoEtag: true, autoLastModified: true
        );

        if (self::getConfig('debug')) {
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
                // https://www.doctrine-project.org/projects/doctrine-dbal/en/4.2/reference/configuration.html#connecting-using-a-url
                'database_url' => $getEnv('TIDY_FEEDBACK_DATABASE_URL'),
                'debug' => (bool) $getEnv('TIDY_FEEDBACK_DEBUG'),
                'default_locale' => $getEnv('TIDY_FEEDBACK_DEFAULT_LOCALE') ?? 'en',
            ];

            if ($users = $getEnv('TIDY_FEEDBACK_USERS')) {
                try {
                    $config['users'] = json_decode($users, true, flags: JSON_THROW_ON_ERROR);
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
        $response = $event->getResponse();
        if (false
          || !$response->isSuccessful()
            // This does not work as expected in Drupal!
            // || !str_starts_with((string)$response->headers->get('content-type'), 'text/html')
        ) {
            return;
        }

        try {
            $widget = $this->getWidget();
            if (empty($widget)) {
                return;
            }

            if ($content = $response->getContent()) {
                $content = preg_replace('~</body>~i', $widget.'$0', (string) $content);
                $response->setContent($content);
            }
        } catch (\Throwable $throwable) {
            if (self::getConfig('debug')) {
                throw $throwable;
            }
            // Ignore all errors!
        }
    }

    public function authorize(Request $request): void
    {
        $users = self::getConfig('users');
        if (empty($users)) {
            return;
        }

        [$user, $password] = [$request->getUser(), $request->getPassword()];
        if (empty($user) || empty($password) || $password !== ($users[$user] ?? null)) {
            throw new UnauthorizedHttpException('Basic realm="Tidy feedback"');
        }
    }
}
