<?php

declare(strict_types=1);

namespace ItkDev\TidyFeedback\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use ItkDev\TidyFeedback\Model\Item;
use ItkDev\TidyFeedback\Model\ItemStatus;
use ItkDev\TidyFeedback\TidyFeedbackHelper;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
        $this->helper->authorize($request);

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
        $this->helper->authorize($request);

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

    public function image(Request $request, int $id): Response
    {
        $this->helper->authorize($request);

        $item = $this->itemRepository->find($id);

        if (null === $item) {
            throw new NotFoundHttpException();
        }

        $data = $item->getData();
        $image = (string) ($data['image'] ?? $data['raw'] ?? null);
        // https://developer.mozilla.org/en-US/docs/Web/URI/Reference/Schemes/data
        if (!preg_match('~^data:(?P<mediaType>image/.+?)(?P<base64>;base64)?,(?P<content>.+)$~', $image, $matches)) {
            throw new NotFoundHttpException();
        }
        $contentType = $matches['mediaType'];

        $content = $matches['content'];
        if (!empty($matches['base64'])) {
            $content = base64_decode($content);
        }
        if (str_contains($contentType, 'svg')) {
            $content = urldecode($content);
        }

        // Send as binary file to set the expected headers.
        $temp = new \SplTempFileObject();
        $temp->fwrite($content);

        return new BinaryFileResponse(
            $temp,
            headers: [
                'content-type' => $contentType,
            ]
        );
    }

    public function post(Request $request): Response
    {
        try {
            $data = $request->toArray();

            $item = (new Item())
                ->setSubject($data['subject'] ?? 'Feedback on '.($data['context']['url'] ?? 'unknown page'))
                ->setCreatedBy($data['created_by'] ?? null)
                ->setStatus(ItemStatus::NEW)
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

    public function check(Request $request): Response
    {
        $url = $request->query->get('url');
        if (empty($url)) {
            return new JsonResponse(['data' => ['count' => 0, 'items' => []]]);
        }

        // Note: JSON_EXTRACT cannot use a standard index, so this query
        // performs a full table scan. Acceptable for small-to-medium tables
        // but may need optimisation (e.g. a dedicated indexed column) if
        // the item table grows large.
        $connection = $this->entityManager->getConnection();
        $count = (int) $connection->fetchOne(
            "SELECT COUNT(*) FROM item WHERE JSON_EXTRACT(data, '$.context.url') = ?",
            [$url]
        );

        $rows = $connection->fetchAllAssociative(
            <<<'SQL'
                SELECT
                    id,
                    JSON_EXTRACT(data, '$.description') AS description,
                    JSON_EXTRACT(data, '$.context.selectedElement') AS selected_element
                FROM item
                WHERE JSON_EXTRACT(data, '$.context.url') = ?
                ORDER BY createdAt DESC
                LIMIT 10
                SQL,
            [$url]
        );

        $items = array_map(fn (array $row) => [
            'description' => json_decode($row['description'], true) ?? $row['description'],
            'url' => $this->helper->generateUrl('tidy_feedback_show', ['id' => $row['id']]),
            'selectedElement' => json_decode($row['selected_element'] ?? 'null', true) ?? $row['selected_element'] ?? null,
        ], $rows);

        return new JsonResponse(['data' => ['count' => $count, 'items' => $items]]);
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
