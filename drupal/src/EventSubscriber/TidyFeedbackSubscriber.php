<?php

declare(strict_types=1);

namespace Drupal\tidy_feedback\EventSubscriber;

use ItkDev\TidyFeedback\TidyFeedbackHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @todo Add description for this subscriber.
 */
final readonly class TidyFeedbackSubscriber implements EventSubscriberInterface {

  public function __construct(
    private TidyFeedbackHelper $helper,
  ) {
  }

  /**
   * Kernel response event handler.
   */
  public function onKernelResponse(ResponseEvent $event): void {
    $response = $event->getResponse();
    if (FALSE
        || !$response->isSuccessful()
          // This does not work as expected in Drupal!
          //      || !str_starts_with((string)$response->headers->get('content-type'), 'text/html')
      ) {
      return;
    }

    try {
      $widget = $this->helper->getWidget();
      if (empty($widget)) {
        return;
      }

      if ($content = $response->getContent()) {
        $content = preg_replace('~</body>~i', $widget . '$0', (string) $content);
        $response->setContent($content);
      }
    }
    catch (\Throwable $t) {
      throw $t;
      // Ignore all errors!
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onKernelResponse'],
    ];
  }

}
