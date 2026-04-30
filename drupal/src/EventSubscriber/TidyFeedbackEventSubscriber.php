<?php

declare(strict_types=1);

namespace Drupal\tidy_feedback\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use ItkDev\TidyFeedback\TidyFeedbackHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class TidyFeedbackEventSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly TidyFeedbackHelper $helper,
    private readonly AccountProxyInterface $currentUser,
  ) {
  }

  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => ['onKernelResponse'],
    ];
  }

  public function onKernelResponse(ResponseEvent $event): void {
    if (!$this->currentUser->hasPermission('use tidy feedback widget')) {
      return;
    }

    $this->helper->onKernelResponse($event);
  }

}
