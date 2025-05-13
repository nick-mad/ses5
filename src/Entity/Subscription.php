<?php

namespace App\Entity;

use App\Repository\SubscriptionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SubscriptionRepository::class)]
#[ORM\Table(name: 'subscriptions')]
#[ORM\UniqueConstraint(name: 'subscription_unique', columns: ['email', 'city'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email', 'city'], message: 'This email is already subscribed to this city.')]
class Subscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['subscription:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['subscription:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(['subscription:read'])]
    private ?string $city = null;

    #[ORM\Column(length: 10)]
    #[Groups(['subscription:read'])]
    private ?string $frequency = null;

    #[ORM\Column]
    #[Groups(['subscription:read'])]
    private bool $confirmed = false;

    #[ORM\Column(type: 'string', length: 36, unique: true)]
    private string $token;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastSentAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['subscription:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->token = Uuid::v4()->toRfc4122();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
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

    public function getFrequency(): ?string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): self
    {
        $this->frequency = $frequency;
        return $this;
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(bool $confirmed): self
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getLastSentAt(): ?\DateTimeImmutable
    {
        return $this->lastSentAt;
    }

    public function setLastSentAt(?\DateTimeInterface $lastSentAt): self
    {
        $anotherDate = $lastSentAt ? \DateTimeImmutable::createFromInterface($lastSentAt) : null;
        $this->lastSentAt = $lastSentAt instanceof \DateTimeImmutable ? $lastSentAt : $anotherDate;
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

    /**
     * Проверка, нужно ли отправлять обновление для данной подписки
     */
    public function shouldSendUpdate(): bool
    {
        if (!$this->confirmed) {
            return false;
        }

        if (!$this->lastSentAt) {
            return true;
        }

        return match($this->frequency) {
            'hourly' => $this->lastSentAt->modify('+1 hour') < new \DateTimeImmutable(),
            'daily' => $this->lastSentAt->modify('+1 day') < new \DateTimeImmutable(),
            default => false,
        };
    }
}