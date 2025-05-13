<?php

namespace App\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        // Логируем только для API запросов
        if (str_starts_with($request->getPathInfo(), '/api')) {
            $this->logger->error('API exception: ' . $exception->getMessage(), [
                'exception' => $exception,
                'request' => $request->getUri()
            ]);

            // Создаем JSON ответ
            $response = $this->createJsonResponse($exception);
            $event->setResponse($response);
        }
    }

    private function createJsonResponse(\Throwable $exception): JsonResponse
    {
        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        $data = [
            'error' => $this->getErrorTypeFromStatusCode($statusCode),
            'message' => $exception instanceof HttpExceptionInterface
                ? $exception->getMessage()
                : 'Internal Server Error',
        ];

        // В production не раскрываем детали для внутренних ошибок
        if ($statusCode === Response::HTTP_INTERNAL_SERVER_ERROR && $_ENV['APP_ENV'] === 'prod') {
            $data['message'] = 'Internal Server Error';
        }

        return new JsonResponse($data, $statusCode);
    }

    private function getErrorTypeFromStatusCode(int $statusCode): string
    {
        return match ($statusCode) {
            Response::HTTP_BAD_REQUEST => 'Bad Request',
            Response::HTTP_UNAUTHORIZED => 'Unauthorized',
            Response::HTTP_FORBIDDEN => 'Forbidden',
            Response::HTTP_NOT_FOUND => 'Not Found',
            Response::HTTP_METHOD_NOT_ALLOWED => 'Method Not Allowed',
            Response::HTTP_REQUEST_TIMEOUT => 'Request Timeout',
            Response::HTTP_CONFLICT => 'Conflict',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
            Response::HTTP_TOO_MANY_REQUESTS => 'Too Many Requests',
            default => $statusCode >= 500 ? 'Server Error' : 'Client Error',
        };
    }
}