<?php

namespace Prolyfix\OnlineCalendarBundle\Widget;

use Doctrine\ORM\EntityManagerInterface;
use Prolyfix\HolidayAndTime\Entity\User;
use Prolyfix\HolidayAndTime\Widget\WidgetInterface;
use Prolyfix\OnlineCalendarBundle\Entity\PatientAppointment;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment as Twig;

class TodayPatientAppointmentsWidget implements WidgetInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private Twig $twig
    ) {
    }

    public function getName(): string
    {
        return "Today's Appointments";
    }

    public function getWidth(): int
    {
        return 4;
    }

    public function getHeight(): int
    {
        return 3;
    }

    public function render(): string
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $this->twig->render('@ProlyfixOnlineCalendar/widget/today_patient_appointments_widget.html.twig', [
                'appointments' => [],
            ]);
        }

        $start = new \DateTimeImmutable('today 00:00:00');
        $end = $start->modify('+1 day');

        $qb = $this->em->getRepository(PatientAppointment::class)->createQueryBuilder('a');
        $qb->where('a.startDate >= :start')
            ->andWhere('a.startDate < :end')
            ->andWhere('a.status != :cancelled')
            ->andWhere('a.owner = :owner')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('cancelled', PatientAppointment::STATUS_CANCELLED)
            ->setParameter('owner', $user)
            ->orderBy('a.startDate', 'ASC')
            ->setMaxResults(10);

        $appointments = $qb->getQuery()->getResult();

        return $this->twig->render('@ProlyfixOnlineCalendar/widget/today_patient_appointments_widget.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    public function getContext(): array
    {
        return [];
    }

    public function isForThisUserAvailable(): bool
    {
        return true;
    }

    public static function getModule(): ?string
    {
        return 'OnlineCalendarBundle';
    }

    public static function isGrantedForRole(): string
    {
        return 'ROLE_USER';
    }
}
