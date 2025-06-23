<?php

declare(strict_types=1);

namespace Drupal\tidy_feedback\Controller;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Drupal\Core\Controller\ControllerBase;
use Drupal\tidy_feedback\Model\Item;
use Drupal\tidy_feedback\TidyFeedbackHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Tidy feedback routes.
 */
final class TidyFeedbackController extends ControllerBase {
  private EntityManagerInterface $entityManager;
  private EntityRepository $itemRepository;

  public function __construct(
  private readonly TidyFeedbackHelper $helper,
)
{
  $this->entityManager = TidyFeedbackHelper::getEntityManager();
  $this->itemRepository = $this->entityManager->getRepository(Item::class);
}

  public function index(): Response
  {
    $items = $this->itemRepository->findBy([], ['createdAt' => Order::Descending->value]);

    return $this->render('index.html.twig', [
      'items' => $items,
    ]);
  }

  public function show(int $id): Response {
    $item = $this->itemRepository->find($id);

    if (null === $item) {
      throw new NotFoundHttpException();
    }

    return $this->render('show.html.twig', [
      'item' => $item,
    ]);
  }

  public function showImage(int $id): Response {
    $item = $this->itemRepository->find($id);

    if (null === $item) {
      throw new NotFoundHttpException();
    }

    $raw = $item->getData()['raw'] ?? null;
    if (null === $raw) {
      throw new NotFoundHttpException();
    }

    return new Response(
      urldecode(preg_replace('/^[^,]+,/', '', $raw)),
      headers: ['content-type: image/svg+xml'],
    );
  }

  public function widget(?string $resource = null): Response {
    switch ($resource) {
      case 'script':
        return new BinaryFileResponse(__DIR__.'/../../build/feedback-widget.js', headers: ['content-type' => 'text/javascript']);
      case 'styles':
        return new BinaryFileResponse(__DIR__.'/../../build/feedback-widget.css', headers: ['content-type' => 'text/css']);
    }

    $widget = $this->helper->getWidget();

    return new Response($widget);
  }

  private function render(string $template, array $context = []): Response
  {
    $content = $this->helper->renderTemplate($template, $context);

    return new Response($content);
  }
}
