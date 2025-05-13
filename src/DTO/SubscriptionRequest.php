<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class SubscriptionRequest
{
    #[Assert\NotBlank(message: 'Email не может быть пустым')]
    #[Assert\Email(message: 'Неверный формат email')]
    #[Assert\Length(max: 255, maxMessage: 'Email не может быть длиннее {{ limit }} символов')]
    public readonly string $email;

    #[Assert\NotBlank(message: 'Город не может быть пустым')]
    #[Assert\Length(max: 255, maxMessage: 'Название города не может быть длиннее {{ limit }} символов')]
    public readonly string $city;

    #[Assert\NotBlank(message: 'Частота не может быть пустой')]
    #[Assert\Choice(choices: ['hourly', 'daily'], message: 'Частота должна быть hourly или daily')]
    public readonly string $frequency;

    public function __construct(string $email, string $city, string $frequency)
    {
        $this->email = $email;
        $this->city = $city;
        $this->frequency = $frequency;
    }
}