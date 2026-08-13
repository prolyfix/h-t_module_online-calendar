<?php

namespace Prolyfix\OnlineCalendarBundle\Widget;

use Doctrine\ORM\EntityManagerInterface;
use Prolyfix\HolidayAndTime\Entity\User;
use Prolyfix\HolidayAndTime\Widget\WidgetInterface;
use Prolyfix\OnlineCalendarBundle\Entity\PatientAppointment;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment as Twig;

class TodayPatientAppointmentsWidget implements WidgetInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private Twig $twig,
        private ?RequestStack $requestStack = null
    ) {
    }

    public function getName(): string
    {
        return "Today's Appointments";
    }

    public function getWidth(): int
    {
        return 6;
    }

    public function getHeight(): int
    {
        return 5;
    }

    public function render(): string
    {
        $request = $this->requestStack?->getCurrentRequest();
        $selectedDate = new \DateTimeImmutable('today');

        if ($request !== null && $request->query->has('date')) {
            $dateInput = (string) $request->query->get('date');
            $parsedDate = \DateTimeImmutable::createFromFormat('Y-m-d', $dateInput) ?: null;
            if ($parsedDate instanceof \DateTimeImmutable) {
                $selectedDate = $parsedDate;
            }
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $this->twig->render('@ProlyfixOnlineCalendar/widget/today_patient_appointments_widget.html.twig', [
                'appointments' => [],
                'selectedDate' => $selectedDate,
                'hours' => range(8, 18),
                'selectedDateLabel' => $selectedDate->format('Y-m-d'),
                'currentTimeMinutes' => null,
                'todayDate' => new \DateTimeImmutable('today'),
            ]);
        }

        $start = $selectedDate->setTime(0, 0, 0);
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
            ->orderBy('a.startDate', 'ASC');

        $appointments = $qb->getQuery()->getResult();

        $schedule = [];
        foreach ($appointments as $appointment) {
            $startDate = $appointment->getStartDate();
            if (!$startDate instanceof \DateTimeInterface) {
                continue;
            }

            $endDate = $appointment->getEndDate();
            if (!$endDate instanceof \DateTimeInterface) {
                $endDate = (clone $startDate)->modify('+30 minutes');
            }

            $startMinutes = ((int) $startDate->format('G')) * 60 + (int) $startDate->format('i');
            $endMinutes = ((int) $endDate->format('G')) * 60 + (int) $endDate->format('i');
            $durationMinutes = max(30, $endMinutes - $startMinutes);
            $schedule[] = [
                'appointment' => $appointment,
                'startMinutes' => $startMinutes,
                'endMinutes' => $endMinutes,
                'durationMinutes' => $durationMinutes,
            ];
        }

        $now = new \DateTimeImmutable('now');
        $currentTimeMinutes = null;
        if ($selectedDate->format('Y-m-d') === $now->format('Y-m-d')) {
            $currentTimeMinutes = ((int) $now->format('G')) * 60 + (int) $now->format('i');
        }

        return $this->twig->render('@ProlyfixOnlineCalendar/widget/today_patient_appointments_widget.html.twig', [
            'appointments' => $appointments,
            'schedule' => $schedule,
            'selectedDate' => $selectedDate,
            'hours' => range(8, 18),
            'selectedDateLabel' => $selectedDate->format('Y-m-d'),
            'currentTimeMinutes' => $currentTimeMinutes,
            'todayDate' => new \DateTimeImmutable('today'),
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
