<?php

namespace App\Controller;

use App\DTO\SubscriptionRequest;
use App\Service\SubscriptionService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class SubscriptionController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly ValidatorInterface $validator
    ) {}

    #[Route('/subscribe', name: 'api_subscribe', methods: ['POST'])]
    #[OA\Tag(name: 'subscription')]
    #[OA\RequestBody(
        description: 'Данные для подписки',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'city', type: 'string'),
                new OA\Property(property: 'frequency', type: 'string', enum: ['hourly', 'daily'])
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Подписка успешно создана'
    )]
    #[OA\Response(
        response: 400,
        description: 'Неверный запрос'
    )]
    #[OA\Response(
        response: 409,
        description: 'Email уже подписан'
    )]
    public function subscribe(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? $request->request->all();

        $email = $data['email'] ?? '';
        $city = $data['city'] ?? '';
        $frequency = $data['frequency'] ?? '';

        $subscriptionRequest = new SubscriptionRequest($email, $city, $frequency);

        $violations = $this->validator->validate($subscriptionRequest);

        if (count($violations) > 0) {
            return $this->json([
                'error' => 'Bad Request',
                'message' => $violations[0]->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $result = $this->subscriptionService->createSubscription($email, $city, $frequency);

            if ($result === 'already_exists') {
                return $this->json([
                    'error' => 'Conflict',
                    'message' => 'Email already subscribed to this city',
                ], Response::HTTP_CONFLICT);
            }

            return $this->json([
                'message' => 'Subscription successful. Confirmation email sent.',
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to create subscription',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/confirm/{token}', name: 'api_confirm_subscription', methods: ['GET'])]
    #[OA\Tag(name: 'subscription')]
    #[OA\Parameter(
        name: 'token',
        description: 'Токен подтверждения',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Подписка успешно подтверждена'
    )]
    #[OA\Response(
        response: 404,
        description: 'Токен не найден'
    )]
    public function confirmSubscription(string $token): JsonResponse
    {
        try {
            $result = $this->subscriptionService->confirmSubscription($token);

            if (!$result) {
                return $this->json([
                    'error' => 'Not Found',
                    'message' => 'Token not found or invalid',
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'message' => 'Subscription confirmed successfully',
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to confirm subscription',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/unsubscribe/{token}', name: 'api_unsubscribe', methods: ['GET'])]
    #[OA\Tag(name: 'subscription')]
    #[OA\Parameter(
        name: 'token',
        description: 'Токен отписки',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Успешная отписка'
    )]
    #[OA\Response(
        response: 404,
        description: 'Токен не найден'
    )]
    public function unsubscribe(string $token): JsonResponse
    {
        try {
            $result = $this->subscriptionService->unsubscribe($token);

            if (!$result) {
                return $this->json([
                    'error' => 'Not Found',
                    'message' => 'Token not found or invalid',
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'message' => 'Unsubscribed successfully',
            ]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to unsubscribe',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}