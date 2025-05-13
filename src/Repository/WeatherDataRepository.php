<?php

namespace App\Repository;

use App\Entity\WeatherData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeatherData>
 */
class WeatherDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeatherData::class);
    }

    /**
     * Найти последний прогноз погоды для города, не старше часа
     */
    public function findLatestForCity(string $city): ?WeatherData
    {
        $oneHourAgo = new \DateTimeImmutable('-1 hour');

        return $this->createQueryBuilder('w')
            ->andWhere('w.city = :city')
            ->andWhere('w.forecastTime > :time')
            ->setParameter('city', $city)
            ->setParameter('time', $oneHourAgo)
            ->orderBy('w.forecastTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}