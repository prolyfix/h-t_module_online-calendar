<?php

namespace Prolyfix\OnlineCalendarBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Prolyfix\OnlineCalendarBundle\Entity\OpenTime;

/**
 * @extends ServiceEntityRepository<OpenTime>
 */
class OpenTimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpenTime::class);
    }

    /**
     * Persist an OpenTime entity
     */
    public function save(OpenTime $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove an OpenTime entity
     */
    public function remove(OpenTime $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
