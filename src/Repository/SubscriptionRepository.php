<?php

namespace App\Repository;

use App\Entity\Subscription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class SubscriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subscription::class);
    }

    /**
     * Найти подписку по токену
     */
    public function findByToken(string $token): ?Subscription
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * Получить подписки, которым нужно отправить обновления
     *
     * @param bool $forceAll Отправить всем подтвержденным подпискам
     * @return array<Subscription>
     */
    public function findSubscriptionsForUpdates(bool $forceAll = false): array
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.confirmed = :confirmed')
            ->setParameter('confirmed', true);

        if (!$forceAll) {
            $now = new \DateTimeImmutable();
            $hourAgo = new \DateTimeImmutable('-1 hour');
            $dayAgo = new \DateTimeImmutable('-1 day');

            $qb->andWhere(
                $qb->expr()->orX(
                // Ни разу не отправляли
                    $qb->expr()->isNull('s.lastSentAt'),
                    // Hourly подписки, прошел час с последней отправки
                    $qb->expr()->andX(
                        $qb->expr()->eq('s.frequency', ':hourly'),
                        $qb->expr()->lt('s.lastSentAt', ':hourAgo')
                    ),
                    // Daily подписки, прошел день с последней отправки
                    $qb->expr()->andX(
                        $qb->expr()->eq('s.frequency', ':daily'),
                        $qb->expr()->lt('s.lastSentAt', ':dayAgo')
                    )
                )
            )
                ->setParameter('hourly', 'hourly')
                ->setParameter('hourAgo', $hourAgo)
                ->setParameter('daily', 'daily')
                ->setParameter('dayAgo', $dayAgo);
        }

        return $qb->getQuery()->getResult();
    }
}