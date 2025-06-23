<?php

namespace Drupal\tidy_feedback\Drush\Commands;

use Doctrine\ORM\Tools\SchemaTool;
use Drupal\tidy_feedback\TidyFeedbackHelper;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 */
final class TidyFeedbackDoctrineCommands extends DrushCommands {

  #[CLI\Command(name: 'tidy-feedback:doctrine:schema-update')]
  public function schemaUpdate() {
    $entityManager = TidyFeedbackHelper::getEntityManager();
    $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();
    $schemaTool = new SchemaTool($entityManager);
    $sql = $schemaTool->getUpdateSchemaSql($metadatas);
    $this->output()->writeln($sql);
    $schemaTool->updateSchema($metadatas);
  }

}
