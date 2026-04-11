<?php

namespace Prolyfix\OnlineCalendarBundle\Repository;

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Prolyfix\OnlineCalendarBundle\Entity\PatientAppointment;
use Prolyfix\PatientManagementBundle\Entity\Patient;

/**
 * @extends ServiceEntityRepository<PatientAppointment>
 */
class PatientAppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatientAppointment::class);
    }

    /**
     * @return PatientAppointment[]
     */
    public function findForPatientInRange(
        Patient $patient,
        DateTimeInterface $start,
        DateTimeInterface $end,
        bool $includeCancelled = false
    ): array {
        $qb = $this->createQueryBuilder('pa')
            ->andWhere('pa.patient = :patient')
            ->andWhere('pa.startDate >= :start')
            ->andWhere('pa.startDate < :end')
            ->setParameter('patient', $patient)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('pa.startDate', 'ASC');

        if (!$includeCancelled) {
            $qb
                ->andWhere('pa.status != :cancelledStatus')
                ->setParameter('cancelledStatus', PatientAppointment::STATUS_CANCELLED);
        }

        return $qb->getQuery()->getResult();
    }
}
