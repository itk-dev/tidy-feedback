<?php

declare(strict_types=1);

namespace ItkDev\TidyFeedbackBundle\Controller;

use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ItkDev\TidyFeedback\Model\Item;
use ItkDev\TidyFeedback\TidyFeedbackHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class TidyFeedbackApiController extends AbstractController
{
    private readonly EntityManagerInterface $entityManager;
    private readonly EntityRepository $itemRepository;

    public function __construct(
    ) {
        $this->entityManager = TidyFeedbackHelper::getEntityManager();
        $this->itemRepository = $this->entityManager->getRepository(Item::class);
    }

    public function index(): Response
    {
        $items = $this->itemRepository->findBy([], ['createdAt' => Order::Descending->value]);

        return new JsonResponse(['data' => $items]);
    }

    public function get(int $id): Response
    {
        $item = $this->itemRepository->find($id);

        if (null === $item) {
            return new JsonResponse(['error' => 'Item not found'], status: Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $item]);
    }

    public function post(Request $request): Response
    {
        try {
            $data = $request->toArray();

            $requiredField = ['subject'];
            // Check required fields.
            $missingFields = array_diff($requiredField, array_keys($data));
            if (!empty($missingFields)) {
                throw new BadRequestHttpException(sprintf('Missing fields: %s', implode(', ', $missingFields)));
            }

            $item = (new Item())
              ->setSubject($data['subject'])
              ->setData($data);
            $this->entityManager->persist($item);
            $this->entityManager->flush();

            return new JsonResponse(['data' => $item], status: Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], status: Response::HTTP_BAD_REQUEST);
        }
    }
}
