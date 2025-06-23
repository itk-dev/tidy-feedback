<?php

declare(strict_types=1);

namespace Drupal\tidy_feedback;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Environment;

final class TidyFeedbackHelper {

  /**
   * Constructs an Example object.
   */
  public function __construct(
    #[Autowire(service: 'twig')]
    private readonly Environment $twig,
  ) {}

  public function getWidget(): string {
    return $this->renderTemplate('widget.html.twig');
  }

  public function renderTemplate(string $path, array $context = []): string {
    $template = $this->twig->createTemplate(file_get_contents(__DIR__.'/../templates/'.$path));
    $widget = $template->render($context);

    return $widget;
  }

  private static EntityManager $entityManager;

  /**
   * @see https://www.doctrine-project.org/projects/doctrine-orm/en/3.3/tutorials/getting-started.html#obtaining-the-entitymanager
   */
  public static function getEntityManager(): EntityManagerInterface
  {
    if (empty(self::$entityManager)) {
      $config = ORMSetup::createAttributeMetadataConfiguration(
        paths: [__DIR__ . '/Model'],
      );

      $connection = DriverManager::getConnection([
        'driver' => 'pdo_sqlite',
        // @todo Get this in a (more) clever way.
        'path' => __DIR__ . '/db.sqlite',
      ], $config);

      self::$entityManager = new EntityManager($connection, $config);
    }

    return self::$entityManager;
  }
}
