<?php

namespace App\Repository;

use App\Entity\Visit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Visit>
 */
class VisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visit::class);
    }

    public function findByParams(array $criteria, ?int $offset = null, ?int $limit = null): array
    {
        $query = $this->createQueryBuilder('v');

        if (! empty($criteria['url'])) {
            $query->andWhere('v.request_url LIKE :url')->setParameter('url', "%{$criteria['url']}%");
        }

        if (! empty($criteria['from'])) {
            $from = \DateTime::createFromFormat('Y-m-d', substr($criteria['from'], 0, 10));
            $query->andWhere('v.created_at >= :from')->setParameter('from', $from->setTime(0, 0));
        }

        if (! empty($criteria['to'])) {
            $to = \DateTime::createFromFormat('Y-m-d', substr($criteria['to'], 0, 10));
            $query->andWhere('v.created_at <= :to')->setParameter('to', $to->setTime(0, 0));
        }

        $query->addOrderBy('v.created_at', 'DESC')
            ->addOrderBy('v.request_url', 'ASC');

        $countQuery = clone $query;
        $count = $countQuery->select('COUNT(v.id)')->getQuery()->getSingleScalarResult();

        return [
            'items' => $query->setFirstResult($offset)
                ->setMaxResults($limit)
                ->getQuery()
                ->getResult(),
            'total' => $count,
        ];
    }

    public function findOneTodayByRequestUrlAndFingerprint(string $requestUrl, string $fp_hash): ?Visit
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.created_at >= :today')
            ->setParameter('today', (new \DateTimeImmutable())->setTime(0, 0))
            ->andWhere('v.request_url = :request_url')
            ->setParameter('request_url', $requestUrl)
            ->andWhere('v.fp_hash = :fp_hash')
            ->setParameter('fp_hash', $fp_hash)
            ->orderBy('v.created_at', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Visit $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
