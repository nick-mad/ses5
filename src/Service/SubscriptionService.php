<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Repository\SubscriptionRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SubscriptionService
{
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailService $emailService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Создать новую подписку на прогноз погоды
     *
     * @param string $email
     * @param string $city
     * @param string $frequency
     * @return string|Subscription
     */
    public function createSubscription(string $email, string $city, string $frequency): string|Subscription
    {
        try {
            // Проверяем, существует ли такая подписка
            $existingSubscription = $this->subscriptionRepository->findOneBy([
                'email' => $email,
                'city' => $city,
            ]);

            if ($existingSubscription) {
                return 'already_exists';
            }

            // Создаем новую подписку
            $subscription = new Subscription();
            $subscription->setEmail($email);
            $subscription->setCity($city);
            $subscription->setFrequency($frequency);

            $this->entityManager->persist($subscription);
            $this->entityManager->flush();

            // Отправляем email для подтверждения
            $this->emailService->sendConfirmationEmail($subscription);

            return $subscription;

        } catch (UniqueConstraintViolationException $e) {
            $this->logger->error('Ошибка при создании подписки (дублирование): ' . $e->getMessage());
            return 'already_exists';
        } catch (\Exception $e) {
            $this->logger->error('Ошибка при создании подписки: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Подтвердить подписку по токену
     *
     * @param string $token
     * @return bool
     */
    public function confirmSubscription(string $token): bool
    {
        $subscription = $this->subscriptionRepository->findByToken($token);

        if (!$subscription) {
            return false;
        }

        $subscription->setConfirmed(true);
        //$subscription->setConfirmationToken('');
        $this->entityManager->flush();

        // Отправляем приветственное сообщение
        $this->emailService->sendWelcomeEmail($subscription);

        return true;
    }

    /**
     * Отписаться от прогноза погоды
     *
     * @param string $token
     * @return bool
     */
    public function unsubscribe(string $token): bool
    {
        $subscription = $this->subscriptionRepository->findByToken($token);

        if (!$subscription) {
            return false;
        }

        $email = $subscription->getEmail();
        $city = $subscription->getCity();

        $this->entityManager->remove($subscription);
        $this->entityManager->flush();

        // Отправляем прощальное сообщение
        $this->emailService->sendUnsubscribeConfirmationEmail($email, $city);

        return true;
    }

    /**
     * Отправить обновления погоды подписчикам
     *
     * @param bool $forceAll Отправить всем подтвержденным подпискам
     * @return int Количество отправленных обновлений
     */
    public function sendWeatherUpdates(bool $forceAll = false): int
    {
        $count = 0;
        $subscriptions = $this->subscriptionRepository->findSubscriptionsForUpdates($forceAll);

        foreach ($subscriptions as $subscription) {
            try {
                $this->emailService->sendWeatherUpdate($subscription);

                $subscription->setLastSentAt(new \DateTimeImmutable());
                $this->entityManager->persist($subscription);
                $count++;

                // Периодически флашим чтобы не держать много в памяти
                if ($count % 10 === 0) {
                    $this->entityManager->flush();
                }
            } catch (\Exception $e) {
                $this->logger->error("Ошибка при отправке обновления для {$subscription->getEmail()}: " . $e->getMessage());
            }
        }

        if ($count % 10 !== 0) {
            $this->entityManager->flush();
        }

        return $count;
    }
}