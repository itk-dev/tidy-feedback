<?php

declare(strict_types=1);

namespace Drupal\tidy_feedback\Controller;

use Drupal\Core\Controller\ControllerBase;
use ItkDev\TidyFeedback\Controller\TidyFeedbackControllerTrait;

/**
 * Returns responses for Tidy feedback routes.
 */
final class TidyFeedbackController extends ControllerBase {
  use TidyFeedbackControllerTrait;

}
