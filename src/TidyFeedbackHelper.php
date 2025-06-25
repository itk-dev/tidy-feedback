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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        return $this->renderTemplate('widget.html.twig');
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
            self::$twig->addFilter(new TwigFilter('trans', fn (string $text) => $text));
            self::$twig->addFunction(new TwigFunction('path', fn (string $name, array $parameters = []) => $this->urlGenerator->generate($name, $parameters)));
        }

        $template = self::$twig->load($path);

        return $template->render($context);
    }

    private static EntityManager $entityManager;

    /**
     * @see https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/tutorials/getting-started.html#obtaining-the-entitymanager
     */
    public static function getEntityManager(): EntityManagerInterface
    {
        if (empty(self::$entityManager)) {
            $config = ORMSetup::createAttributeMetadataConfiguration(
                paths: [__DIR__.'/Model'],
            );

            $dsn = static::getConfig('database_url');
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

        return new BinaryFileResponse($filename, headers: [
            'content-type' => match (pathinfo($filename, PATHINFO_EXTENSION)) {
                'css' => 'text/css',
                'js' => 'text/javascript',
                default => throw new NotFoundHttpException(),
            },
        ]);
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

    private static function getConfig(?string $name): mixed
    {
        $getEnv = static fn (string $name) => getenv($name) ?: ($_ENV[$name] ?? null);

        $config = [
            // https://www.doctrine-project.org/projects/doctrine-dbal/en/4.2/reference/configuration.html#connecting-using-a-url
            'database_url' => $getEnv('TIDY_FEEDBACK_DATABASE_URL'),
            'debug' => (bool) $getEnv('TIDY_FEEDBACK_DEBUG'),
        ];

        return $name ? ($config[$name] ?? null) : $config;
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
            if (static::getConfig('debug')) {
                throw $throwable;
            }
            // Ignore all errors!
        }
    }
}
