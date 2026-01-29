<?php

namespace Prolyfix\OnlineCalendarBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Prolyfix\OnlineCalendarBundle\Entity\PatientAppointment;

/**
 * @extends ServiceEntityRepository<PatientAppointment>
 */
class PatientAppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatientAppointment::class);
    }
}
