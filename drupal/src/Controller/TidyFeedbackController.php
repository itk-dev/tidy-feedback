<?php

declare(strict_types=1);

namespace Drupal\tidy_feedback\Controller;

use Drupal\Core\Controller\ControllerBase;
use ItkDev\TidyFeedback\Controller\TidyFeedbackControllerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Tidy feedback routes.
 */
final class TidyFeedbackController extends ControllerBase {
  use TidyFeedbackControllerTrait;

  /**
   * {@inheritdoc}
   */
  public function index(Request $request): Response|array {
    $format = $this->getFormat($request);

    if (self::FORMAT_HTML !== $format) {
      return parent::index($request);
    }

    $this->helper->authorize($request);
    $items = $this->itemRepository->findBy([], ['createdAt' => 'DESC']);

    return [
      '#markup' => $this->helper->renderTemplate('index.html.twig', ['items' => $items]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function show(Request $request, string $id): Response|array {
    $format = $this->getFormat($request);

    if (self::FORMAT_HTML !== $format || 'test' === $id) {
      return parent::show($request, $id);
    }

    $this->helper->authorize($request);
    $item = $this->itemRepository->find((int) $id);

    if (null === $item) {
      throw new NotFoundHttpException('Item not found');
    }

    return [
      '#markup' => $this->helper->renderTemplate('show.html.twig', ['item' => $item]),
    ];
  }

}
