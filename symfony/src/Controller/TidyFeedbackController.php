<?php

namespace ItkDev\TidyFeedbackBundle\Controller;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ItkDev\TidyFeedback\Model\Item;
use ItkDev\TidyFeedback\TidyFeedbackHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TidyFeedbackController extends AbstractController
{
    private readonly EntityManagerInterface $entityManager;
    private readonly EntityRepository $itemRepository;

    public function __construct(
        private readonly TidyFeedbackHelper $helper,
    ) {
        $this->entityManager = TidyFeedbackHelper::getEntityManager();
        $this->itemRepository = $this->entityManager->getRepository(Item::class);
    }

    public function index(): Response
    {
        $items = $this->itemRepository->findBy([], ['createdAt' => Order::Descending->value]);

        return $this->helper->renderResponse('index.html.twig', [
            'items' => $items,
        ]);
    }

    public function show(int $id): Response
    {
        $item = $this->itemRepository->find($id);

        if (null === $item) {
            throw new NotFoundHttpException();
        }

        return $this->helper->renderResponse('show.html.twig', [
            'item' => $item,
        ]);
    }

    public function showImage(int $id): Response
    {
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

    public function widget(?string $resource = null): Response
    {
        return $this->helper->createWidgetResponse($resource);
    }
}
