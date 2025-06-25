<?php

declare(strict_types=1);

namespace ItkDev\TidyFeedback\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ItkDev\TidyFeedback\Model\Item;
use ItkDev\TidyFeedback\TidyFeedbackHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Tidy feedback routes.
 */
trait TidyFeedbackControllerTrait
{
    private const FORMAT_HTML = 'html';
    private const FORMAT_JSON = 'json';

    private readonly EntityManagerInterface $entityManager;
    private readonly EntityRepository $itemRepository;

    public function __construct(
        private readonly TidyFeedbackHelper $helper,
    ) {
        $this->entityManager = TidyFeedbackHelper::getEntityManager();
        $this->itemRepository = $this->entityManager->getRepository(Item::class);
    }

    public function index(Request $request): Response
    {
        $items = $this->itemRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->createResponse(
            $request,
            'index.html.twig',
            [
                'items' => $items,
            ],
            dataKey: 'items',
        );
    }

    public function show(Request $request, string $id): Response
    {
        if ('test' === $id) {
            return $this->helper->renderResponse($id.'.html.twig');
        }

        $item = $this->itemRepository->find((int) $id);

        if (null === $item) {
            return $this->createExceptionResponse($request, new NotFoundHttpException('Item not found'));
        }

        return $this->createResponse(
            $request,
            'show.html.twig',
            [
                'item' => $item,
            ],
            dataKey: 'item',
        );
    }

    public function image(int $id): Response
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
        } catch (\Exception $exception) {
            if (!$exception instanceof HttpException) {
                $exception = new HttpException(
                    Response::HTTP_INTERNAL_SERVER_ERROR,
                    $exception->getMessage(),
                    previous: $exception
                );
            }

            return $this->createExceptionResponse(
                $request,
                $exception
            );
        }
    }

    public function asset(string $asset): Response
    {
        return $this->helper->createAssetResponse($asset);
    }

    private function getFormat(Request $request): string
    {
        // Allow overriding format with query string paramenter.
        $format = $request->query->get('_format');
        if ($format && in_array($format, [self::FORMAT_HTML, self::FORMAT_JSON])) {
            return $format;
        }

        $contentTypes = $request->getAcceptableContentTypes();

        return match (true) {
            in_array('text/html', $contentTypes) => self::FORMAT_HTML,
            default => self::FORMAT_JSON,
        };
    }

    private function createResponse(Request $request, string $template, array $context, string $dataKey = 'data'): Response
    {
        $format = $this->getFormat($request);

        return match ($format) {
            self::FORMAT_HTML => $this->helper->renderResponse($template, $context),
            default => new JsonResponse(['data' => $context[$dataKey]]),
        };
    }

    private function createExceptionResponse(Request $request, HttpException $exception): Response
    {
        $format = $this->getFormat($request);

        return match ($format) {
            self::FORMAT_HTML => throw $exception,
            default => new JsonResponse([
                'status' => $exception->getStatusCode(),
                'message' => $exception->getMessage(),
            ], status: $exception->getStatusCode()),
        };
    }
}
