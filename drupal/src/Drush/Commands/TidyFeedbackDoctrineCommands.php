<?php

namespace Drupal\tidy_feedback\Drush\Commands;

use ItkDev\TidyFeedback\TidyFeedbackHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
  name: 'tidy-feedback:doctrine:schema-update',
  description: 'Update Tidy feedback database schema',
)]
final class TidyFeedbackDoctrineCommands extends Command {
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    return TidyFeedbackHelper::updateSchema($output) ? Command::SUCCESS : Command::FAILURE;
  }

}
