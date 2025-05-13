<?php

namespace App\Controller;

use App\Service\WeatherService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class WeatherController extends AbstractController
{
    public function __construct(
        private readonly WeatherService     $weatherService,
        private readonly ValidatorInterface $validator
    )
    {
    }

    #[Route('/weather', name: 'api_get_weather', methods: ['GET'])]
    #[OA\Tag(name: 'weather')]
    #[OA\Parameter(
        name: 'city',
        description: 'Город для получения прогноза погоды',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Успешная операция - прогноз погоды',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'temperature', type: 'number'),
            new OA\Property(property: 'humidity', type: 'number'),
            new OA\Property(property: 'description', type: 'string')
        ])
    )]
    #[OA\Response(response: 400, description: 'Неверный запрос')]
    #[OA\Response(response: 404, description: 'Город не найден')]
    public function getWeather(Request $request): JsonResponse
    {
        $city = $request->query->get('city');

        // Валидация
        $constraints = new Assert\Collection([
            'city' => [
                new Assert\NotBlank(message: 'Город не может быть пустым'),
                new Assert\Type('string'),
                new Assert\Length(['max' => 255]),
            ],
        ]);

        $violations = $this->validator->validate(['city' => $city], $constraints);

        if (count($violations) > 0) {
            return $this->json([
                'error' => 'Bad Request',
                'message' => $violations[0]->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            try {
                $weather = $this->weatherService->getWeatherForCity($city);
            } catch (\Exception $e) {
                return $this->json([
                    'error' => 'Not Found',
                    'message' => $e->getMessage(),
                ], Response::HTTP_NOT_FOUND);
            }

            if (!$weather) {
                return $this->json([
                    'error' => 'Not Found',
                    'message' => 'City not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json($weather->toArray());

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Internal Server Error',
                'message' => 'Failed to retrieve weather data',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}