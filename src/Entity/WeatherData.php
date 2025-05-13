<?php

namespace App\Entity;

use App\Repository\WeatherDataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: WeatherDataRepository::class)]
#[ORM\Table(name: 'weather_data')]
#[ORM\Index(name: 'city_idx', columns: ['city'])]
#[ORM\HasLifecycleCallbacks]
class WeatherData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['weather:read'])]
    private ?string $city = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Groups(['weather:read'])]
    private ?float $temperature = null;

    #[ORM\Column]
    #[Groups(['weather:read'])]
    private ?int $humidity = null;

    #[ORM\Column(length: 255)]
    #[Groups(['weather:read'])]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['weather:read'])]
    private ?\DateTimeImmutable $forecastTime = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getTemperature(): ?float
    {
        return $this->temperature;
    }

    public function setTemperature(float $temperature): self
    {
        $this->temperature = $temperature;
        return $this;
    }

    public function getHumidity(): ?int
    {
        return $this->humidity;
    }

    public function setHumidity(int $humidity): self
    {
        $this->humidity = $humidity;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getForecastTime(): ?\DateTimeImmutable
    {
        return $this->forecastTime;
    }

    public function setForecastTime(\DateTimeInterface $forecastTime): self
    {
        $this->forecastTime = $forecastTime instanceof \DateTimeImmutable
            ? $forecastTime
            : \DateTimeImmutable::createFromInterface($forecastTime);
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Метод для представления объекта в виде массива для API
    public function toArray(): array
    {
        return [
            'temperature' => (float)$this->temperature,
            'humidity' => $this->humidity,
            'description' => $this->description
        ];
    }
}