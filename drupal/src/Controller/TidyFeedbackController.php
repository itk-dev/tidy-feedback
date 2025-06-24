<?php

declare(strict_types=1);

namespace Drupal\tidy_feedback\Controller;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Drupal\Core\Controller\ControllerBase;
use ItkDev\TidyFeedback\Model\Item;
use ItkDev\TidyFeedback\TidyFeedbackHelper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Tidy feedback routes.
 */
final class TidyFeedbackController extends ControllerBase {
  private readonly EntityManagerInterface $entityManager;
  private readonly EntityRepository $itemRepository;

  public function __construct(
    private readonly TidyFeedbackHelper $helper,
  ) {
    $this->entityManager = TidyFeedbackHelper::getEntityManager();
    $this->itemRepository = $this->entityManager->getRepository(Item::class);
  }

  /**
   *
   */
  public function index(): Response {
    $items = $this->itemRepository->findBy([], ['createdAt' => Order::Descending->value]);

    return $this->helper->renderResponse('index.html.twig', [
      'items' => $items,
    ]);
  }

  /**
   *
   */
  public function show(int $id): Response {
    $item = $this->itemRepository->find($id);

    if (NULL === $item) {
      throw new NotFoundHttpException();
    }

    return $this->helper->renderResponse('show.html.twig', [
      'item' => $item,
    ]);
  }

  /**
   *
   */
  public function showImage(int $id): Response {
    $item = $this->itemRepository->find($id);

    if (NULL === $item) {
      throw new NotFoundHttpException();
    }

    $raw = $item->getData()['raw'] ?? NULL;
    if (NULL === $raw) {
      throw new NotFoundHttpException();
    }

    return new Response(
          urldecode(preg_replace('/^[^,]+,/', '', $raw)),
          headers: ['content-type: image/svg+xml'],
      );
  }

  /**
   *
   */
  public function widget(?string $resource = NULL): Response {
    return $this->helper->createWidgetResponse($resource);
  }

}
