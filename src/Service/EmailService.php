<?php

namespace App\Service;

use App\Entity\Subscription;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EmailService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly WeatherService $weatherService
    ) {}

    /**
     * Отправить email для подтверждения подписки
     */
    public function sendConfirmationEmail(Subscription $subscription): void
    {
        try {
            $confirmationUrl = $this->urlGenerator->generate(
                'api_confirm_subscription',
                ['token' => $subscription->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $email = (new Email())
                ->from('weather@example.com')
                ->to($subscription->getEmail())
                ->subject('Подтвердите подписку на прогноз погоды')
                ->html(
                    "Здравствуйте,<br><br>".
                    "Пожалуйста, подтвердите вашу подписку на прогноз погоды для города {$subscription->getCity()}.<br>".
                    "Частота обновлений: " . ($subscription->getFrequency() === 'hourly' ? 'Ежечасно' : 'Ежедневно') . "<br><br>".
                    "<a href=\"{$confirmationUrl}\">Подтвердить подписку</a><br><br>".
                    "Если вы не запрашивали эту подписку, просто проигнорируйте это письмо."
                );

            // В режиме разработки логируем, а не отправляем
            $this->logger->info("Отправка письма для подтверждения подписки на почту {$subscription->getEmail()}");
            $this->logger->info("Ссылка подтверждения: {$confirmationUrl}");

            $this->mailer->send($email);

        } catch (\Exception $e) {
            $this->logger->error('Ошибка при отправке письма подтверждения: ' . $e->getMessage());
        }
    }

    /**
     * Отправить приветственное сообщение после подтверждения подписки
     */
    public function sendWelcomeEmail(Subscription $subscription): void
    {
        try {
            $unsubscribeUrl = $this->urlGenerator->generate(
                'api_unsubscribe',
                ['token' => $subscription->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $email = (new Email())
                ->from('weather@example.com')
                ->to($subscription->getEmail())
                ->subject('Подписка на прогноз погоды подтверждена')
                ->html(
                    "Здравствуйте,<br><br>".
                    "Ваша подписка на прогноз погоды для города {$subscription->getCity()} успешно подтверждена.<br>".
                    "Вы будете получать обновления " . ($subscription->getFrequency() === 'hourly' ? 'каждый час' : 'каждый день') . ".<br><br>".
                    "Если вы захотите отписаться, используйте эту ссылку: <a href=\"{$unsubscribeUrl}\">Отписаться</a><br><br>".
                    "Спасибо за использование нашего сервиса!"
                );

            // В режиме разработки логируем, а не отправляем
            $this->logger->info("Отправка приветственного письма на почту {$subscription->getEmail()}");
            $this->logger->info("Ссылка на отписку: {$unsubscribeUrl}");

            $this->mailer->send($email);

        } catch (\Exception $e) {
            $this->logger->error('Ошибка при отправке приветственного письма: ' . $e->getMessage());
        }
    }

    /**
     * Отправить обновление погоды подписчику
     */
    public function sendWeatherUpdate(Subscription $subscription): void
    {
        try {
            $weather = $this->weatherService->getWeatherForCity($subscription->getCity());

            if (!$weather) {
                $this->logger->error("Не удалось получить данные о погоде для города {$subscription->getCity()}");
                return;
            }

            $unsubscribeUrl = $this->urlGenerator->generate(
                'api_unsubscribe',
                ['token' => $subscription->getToken()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $email = (new Email())
                ->from('weather@example.com')
                ->to($subscription->getEmail())
                ->subject("Прогноз погоды для {$subscription->getCity()}")
                ->html(
                    "Здравствуйте,<br><br>".
                    "Текущая погода в городе {$subscription->getCity()}:<br>".
                    "Температура: {$weather->getTemperature()}°C<br>".
                    "Влажность: {$weather->getHumidity()}%<br>".
                    "Описание: {$weather->getDescription()}<br><br>".
                    "Если вы захотите отписаться, используйте эту ссылку: <a href=\"{$unsubscribeUrl}\">Отписаться</a>"
                );

            // В режиме разработки логируем, а не отправляем
            $this->logger->info("Отправка обновления погоды для {$subscription->getCity()} на почту {$subscription->getEmail()}");
            $this->logger->info("Текущая температура: {$weather->getTemperature()}°C, {$weather->getDescription()}");

            $this->mailer->send($email);

        } catch (\Exception $e) {
            $this->logger->error('Ошибка при отправке обновления погоды: ' . $e->getMessage());
        }
    }

    /**
     * Отправить подтверждение отписки
     */
    public function sendUnsubscribeConfirmationEmail(string $unsubscribeEmail, string $city): void
    {
        try {
            $email = (new Email())
                ->from('weather@example.com')
                ->to($unsubscribeEmail)
                ->subject('Вы отписались от прогноза погоды')
                ->html(
                    "Здравствуйте,<br><br>".
                    "Вы успешно отписались от прогноза погоды для города {$city}.<br><br>".
                    "Спасибо за использование нашего сервиса! Мы будем рады видеть вас снова."
                );

            // В режиме разработки логируем, а не отправляем
            $this->logger->info("Отправка подтверждения отписки для города {$city} на почту {$unsubscribeEmail}");

            $this->mailer->send($email);

        } catch (\Exception $e) {
            $this->logger->error('Ошибка при отправке подтверждения отписки: ' . $e->getMessage());
        }
    }
}