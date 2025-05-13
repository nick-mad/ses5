<?php

namespace App\Service;

use App\Entity\WeatherData;
use App\Repository\WeatherDataRepository;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class WeatherService
{
    private Client $httpClient;

    public function __construct(
        private readonly WeatherDataRepository $weatherRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        private readonly string $weatherApiKey,
        private readonly string $weatherApiUrl
    ) {
        $this->httpClient =  new Client([
            'timeout' => 10,
            'connect_timeout' => 5,
            'http_errors' => false // Не выбрасывать исключение при HTTP-ошибках
        ]);
    }

    /**
     * Получить данные о погоде для указанного города
     */
    public function getWeatherForCity(string $city): ?WeatherData
    {
        // Проверяем кеш сначала (храним 30 минут)
        $cacheKey = 'weather_' . strtolower(str_replace(' ', '_', $city));
        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($city) {
                $item->expiresAfter(1800); // 30 минут

                // Проверяем последние данные в БД (не старше 1 часа)
                $recentWeather = $this->weatherRepository->findLatestForCity($city);

                if ($recentWeather) {
                    return $recentWeather;
                }

                // Получаем новые данные из API
                return $this->fetchWeatherFromApi($city);
            });
        } catch (\Exception $e) {
            $this->logger->error('Ошибка при получении данных погоды: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Получить данные о погоде из внешнего API
     */
    private function fetchWeatherFromApi(string $city): WeatherData
    {
        try {
            // Делаем запрос к Weather API
            $response = $this->httpClient->get($this->weatherApiUrl . '/current.json', [
                'query' => [
                    'key' => $this->weatherApiKey,
                    'q' => $city,
                    'lang' => 'uk',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!empty($data['error']['message'])) {
                throw new \Exception($data['error']['message']);
            }

            // Проверяем, что данные получены корректно
            if (!isset($data['current']) || !isset($data['location'])) {
                throw new \Exception('Некорректные данные получены от API погоды');
            }

            // Создаем объект с данными о погоде
            $weather = new WeatherData();
            $weather->setCity($city);
            $weather->setTemperature($data['current']['temp_c']); // Температура в Цельсиях
            $weather->setHumidity($data['current']['humidity']); // Влажность в процентах
            $weather->setDescription($data['current']['condition']['text']); // Текстовое описание погоды
            $weather->setForecastTime(new \DateTimeImmutable('@' . $data['current']['last_updated_epoch'])); // Время последнего обновления

            $this->entityManager->persist($weather);
            $this->entityManager->flush();

            return $weather;
        } catch (\Exception $e) {
            $this->logger->error('Ошибка при получении данных из API погоды: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Получить случайное описание погоды для демонстрации
     */
    private function getRandomWeatherDescription(): string
    {
        $descriptions = [
            'Ясно',
            'Облачно',
            'Пасмурно',
            'Дождь',
            'Гроза',
            'Снег',
            'Туман'
        ];

        return $descriptions[array_rand($descriptions)];
    }
}