<?php

namespace Prolyfix\OnlineCalendarBundle\Controller\Admin;

use DateTimeImmutable;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Prolyfix\HolidayAndTime\Controller\Admin\BaseCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Prolyfix\HolidayAndTime\Entity\User;
use Prolyfix\OnlineCalendarBundle\Entity\OpenTime;
use Prolyfix\OnlineCalendarBundle\Entity\PatientAppointment;
use Symfony\Component\HttpFoundation\Response;

class PatientAppointmentCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return PatientAppointment::class;
    }

    public function createEntity(string $entityFqcn): PatientAppointment
    {
        $appointment = new PatientAppointment();
        $request = $this->container->get('request_stack')->getCurrentRequest();

        if ($request === null) {
            return $appointment;
        }

        $slotDate = (string) $request->query->get('slotDate', '');
        $slotHour = $request->query->get('slotHour');

        if ($slotDate !== '' && is_numeric($slotHour)) {
            $hour = max(0, min(23, (int) $slotHour));
            $start = DateTimeImmutable::createFromFormat('Y-m-d H:i', sprintf('%s %02d:00', $slotDate, $hour));

            if ($start instanceof DateTimeImmutable) {
                $appointment->setStartDate(\DateTime::createFromImmutable($start));
                $appointment->setEndDate(\DateTime::createFromImmutable($start->modify('+1 hour')));
            }
        }

        $slotDoctorId = $request->query->getInt('slotDoctorId');
        if ($slotDoctorId > 0) {
            $doctor = $this->em->getRepository(User::class)->find($slotDoctorId);
            if ($doctor instanceof User) {
                $appointment->setOwner($doctor);
            }
        }

        return $appointment;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            AssociationField::new('patient')->renderAsNativeWidget(),
            AssociationField::new('appointmentType')->renderAsNativeWidget(),
            ChoiceField::new('status')
                ->setChoices([
                    'online_calendar.status.active' => PatientAppointment::STATUS_ACTIVE,
                    'online_calendar.status.validated' => PatientAppointment::STATUS_VALIDATED,
                    'online_calendar.status.cancelled' => PatientAppointment::STATUS_CANCELLED,
                ])
                ->renderAsBadges([
                    PatientAppointment::STATUS_ACTIVE => 'success',
                    PatientAppointment::STATUS_VALIDATED => 'primary',
                    PatientAppointment::STATUS_CANCELLED => 'danger',
                ]),
            DateTimeField::new('startDate'),
            DateTimeField::new('endDate'),
            TextField::new('emailAddress')->hideOnIndex(),
            TextField::new('firstName')->hideOnIndex(),
            TextField::new('lastName')->hideOnIndex(),
            TextField::new('phone')->hideOnIndex(),
            TextEditorField::new('description')->hideOnIndex(),
        ];
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters->add(
            ChoiceFilter::new('status')
                ->setChoices([
                    'online_calendar.status.active' => PatientAppointment::STATUS_ACTIVE,
                    'online_calendar.status.validated' => PatientAppointment::STATUS_VALIDATED,
                    'online_calendar.status.cancelled' => PatientAppointment::STATUS_CANCELLED,
                ])
                ->canSelectMultiple()
        );
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters
    ): QueryBuilder {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $request = $this->container->get('request_stack')->getCurrentRequest();
        $appliedFilters = $request?->query->all('filters') ?? [];

        if (!array_key_exists('status', $appliedFilters)) {
            $qb->andWhere('entity.status != :defaultCancelledStatus')
                ->setParameter('defaultCancelledStatus', PatientAppointment::STATUS_CANCELLED);
        }

        return $qb;
    }

    public function weekView(
        AdminContext $context,
        AdminUrlGenerator $adminUrlGenerator
    ): Response {
        $request = $context->getRequest();
        $today = new DateTimeImmutable('today');
        $view = $request->query->get('view', 'week');
        $view = in_array($view, ['week', 'day'], true) ? $view : 'week';
        $selectedUserId = $request->query->getInt('userId');

        $selectedDateInput = (string) $request->query->get('date', $today->format('Y-m-d'));
        $selectedDate = DateTimeImmutable::createFromFormat('Y-m-d', $selectedDateInput) ?: $today;

        if ($view === 'day') {
            $rangeStart = $selectedDate->setTime(0, 0);
            $rangeEnd = $rangeStart->modify('+1 day');
            $weekStart = $rangeStart;
            $weekEnd = $rangeStart;
        } else {
            $week = (int) $request->query->get('week', $today->format('W'));
            $year = (int) $request->query->get('year', $today->format('o'));
            $weekStart = (new DateTimeImmutable())->setISODate($year, $week)->setTime(0, 0);
            $rangeStart = $weekStart;
            $rangeEnd = $weekStart->modify('+1 week');
            $weekEnd = $rangeEnd->modify('-1 day');
        }

        $qb = $this->em->getRepository(PatientAppointment::class)->createQueryBuilder('a');
        $qb->where('a.startDate >= :rangeStart')
            ->andWhere('a.startDate < :rangeEnd')
            ->andWhere('a.status != :cancelled')
            ->setParameter('rangeStart', $rangeStart)
            ->setParameter('rangeEnd', $rangeEnd)
            ->setParameter('cancelled', PatientAppointment::STATUS_CANCELLED)
            ->orderBy('a.startDate', 'ASC');

        if ($selectedUserId > 0) {
            $qb->andWhere('a.owner = :ownerId')
                ->setParameter('ownerId', $selectedUserId);
        }

        $appointments = $qb->getQuery()->getResult();

        $days = [];
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        if ($view === 'day') {
            $days[$rangeStart->format('Y-m-d')] = [
                'date' => $rangeStart,
                'dayName' => $rangeStart->format('l'),
                'appointments' => [],
            ];
        } else {
            for ($offset = 0; $offset < 7; $offset++) {
                $day = $weekStart->modify(sprintf('+%d day', $offset));
                $dayKey = $day->format('Y-m-d');
                $days[$dayKey] = [
                    'date' => $day,
                    'dayName' => $dayNames[$offset],
                    'appointments' => [],
                ];
            }
        }

        foreach ($appointments as $appointment) {
            $key = $appointment->getStartDate()?->format('Y-m-d');
            if ($key !== null && isset($days[$key])) {
                $days[$key]['appointments'][] = $appointment;
            }
        }

        $hours = range(8, 18);

        $openTimeWeekdays = array_values(array_unique(array_map(
            static fn (array $day): string => (string) $day['dayName'],
            $days
        )));

        $openTimeQb = $this->em->getRepository(OpenTime::class)->createQueryBuilder('o');
        $openTimeQb
            ->leftJoin('o.user', 'u')->addSelect('u')
            ->where('o.weekday IN (:weekdays)')
            ->andWhere('o.user IS NOT NULL')
            ->setParameter('weekdays', $openTimeWeekdays)
            ->orderBy('u.name', 'ASC')
            ->addOrderBy('o.weekday', 'ASC')
            ->addOrderBy('o.startTime', 'ASC');

        if ($selectedUserId > 0) {
            $openTimeQb
                ->andWhere('u.id = :selectedOpenTimeUserId')
                ->setParameter('selectedOpenTimeUserId', $selectedUserId);
        }

        /** @var OpenTime[] $openTimes */
        $openTimes = $openTimeQb->getQuery()->getResult();

        $doctorsById = [];
        $openTimesByWeekdayDoctor = [];

        foreach ($openTimes as $openTime) {
            $doctor = $openTime->getUser();
            if (!$doctor instanceof User || $doctor->getId() === null) {
                continue;
            }

            $doctorId = $doctor->getId();
            $weekday = (string) $openTime->getWeekday();

            $doctorsById[$doctorId] = $doctor;
            $openTimesByWeekdayDoctor[$weekday][$doctorId][] = $openTime;
        }

        $doctors = array_values($doctorsById);
        usort($doctors, static function (User $left, User $right): int {
            return strcmp((string) $left->getName(), (string) $right->getName());
        });

        $toMinutes = static function (?\DateTimeInterface $time): ?int {
            if ($time === null) {
                return null;
            }

            return ((int) $time->format('H')) * 60 + (int) $time->format('i');
        };

        $intersects = static function (int $startA, int $endA, int $startB, int $endB): bool {
            return $startA < $endB && $startB < $endA;
        };

        $slotStatusByDayDoctorHour = [];
        foreach ($days as $dayKey => $day) {
            $weekday = (string) $day['dayName'];
            foreach ($doctors as $doctor) {
                $doctorId = $doctor->getId();
                if ($doctorId === null) {
                    continue;
                }

                $doctorOpenTimes = $openTimesByWeekdayDoctor[$weekday][$doctorId] ?? [];

                foreach ($hours as $hour) {
                    $cellStart = $hour * 60;
                    $cellEnd = ($hour + 1) * 60;
                    $status = 'closed';

                    foreach ($doctorOpenTimes as $openTime) {
                        $openStart = $toMinutes($openTime->getStartTime());
                        $openEnd = $toMinutes($openTime->getEndTime());
                        if ($openStart === null || $openEnd === null || !$intersects($cellStart, $cellEnd, $openStart, $openEnd)) {
                            continue;
                        }

                        $status = 'open';

                        $breakStart = $toMinutes($openTime->getBreakFrom());
                        $breakEnd = $toMinutes($openTime->getBreakTo());
                        if ($breakStart !== null && $breakEnd !== null && $intersects($cellStart, $cellEnd, $breakStart, $breakEnd)) {
                            $status = 'break';
                        }
                    }

                    $slotStatusByDayDoctorHour[$dayKey][$doctorId][$hour] = $status;
                }
            }
        }

        $appointmentsByDayDoctorHour = [];
        foreach ($appointments as $appointment) {
            $dayKey = $appointment->getStartDate()?->format('Y-m-d');
            $doctorId = $appointment->getOwner()?->getId();
            if ($dayKey === null || $doctorId === null || !isset($days[$dayKey])) {
                continue;
            }

            $hour = (int) $appointment->getStartDate()?->format('G');
            $appointmentsByDayDoctorHour[$dayKey][$doctorId][$hour][] = $appointment;
        }

        if ($view === 'day') {
            $previousRef = $rangeStart->modify('-1 day');
            $nextRef = $rangeStart->modify('+1 day');

            $previousUrl = (clone $adminUrlGenerator)
                ->setController(self::class)
                ->setAction('weekView')
                ->set('view', 'day')
                ->set('date', $previousRef->format('Y-m-d'))
                ->set('userId', $selectedUserId > 0 ? $selectedUserId : null)
                ->generateUrl();

            $currentUrl = (clone $adminUrlGenerator)
                ->setController(self::class)
                ->setAction('weekView')
                ->set('view', 'day')
                ->set('date', $today->format('Y-m-d'))
                ->set('userId', $selectedUserId > 0 ? $selectedUserId : null)
                ->generateUrl();

            $nextUrl = (clone $adminUrlGenerator)
                ->setController(self::class)
                ->setAction('weekView')
                ->set('view', 'day')
                ->set('date', $nextRef->format('Y-m-d'))
                ->set('userId', $selectedUserId > 0 ? $selectedUserId : null)
                ->generateUrl();
        } else {
            $previousWeek = $weekStart->modify('-1 week');
            $nextWeek = $weekStart->modify('+1 week');

            $previousUrl = (clone $adminUrlGenerator)
                ->setController(self::class)
                ->setAction('weekView')
                ->set('view', 'week')
                ->set('week', (int) $previousWeek->format('W'))
                ->set('year', (int) $previousWeek->format('o'))
                ->set('userId', $selectedUserId > 0 ? $selectedUserId : null)
                ->generateUrl();

            $currentUrl = (clone $adminUrlGenerator)
                ->setController(self::class)
                ->setAction('weekView')
                ->set('view', 'week')
                ->set('week', (int) $today->format('W'))
                ->set('year', (int) $today->format('o'))
                ->set('userId', $selectedUserId > 0 ? $selectedUserId : null)
                ->generateUrl();

            $nextUrl = (clone $adminUrlGenerator)
                ->setController(self::class)
                ->setAction('weekView')
                ->set('view', 'week')
                ->set('week', (int) $nextWeek->format('W'))
                ->set('year', (int) $nextWeek->format('o'))
                ->set('userId', $selectedUserId > 0 ? $selectedUserId : null)
                ->generateUrl();
        }

        $toggleDayUrl = (clone $adminUrlGenerator)
            ->setController(self::class)
            ->setAction('weekView')
            ->set('view', 'day')
            ->set('date', $today->format('Y-m-d'))
            ->set('userId', $selectedUserId > 0 ? $selectedUserId : null)
            ->generateUrl();

        $toggleWeekUrl = (clone $adminUrlGenerator)
            ->setController(self::class)
            ->setAction('weekView')
            ->set('view', 'week')
            ->set('week', (int) $today->format('W'))
            ->set('year', (int) $today->format('o'))
            ->set('userId', $selectedUserId > 0 ? $selectedUserId : null)
            ->generateUrl();

        $users = [];
        $currentUser = $this->getUser();
        if ($currentUser instanceof User && $currentUser->getCompany() !== null) {
            $users = $this->em->getRepository(User::class)->findBy([
                'company' => $currentUser->getCompany(),
            ], [
                'name' => 'ASC',
            ]);
        }
        $isWorkingOnDay= [];
        dump($slotStatusByDayDoctorHour);
        foreach($slotStatusByDayDoctorHour as $dayKey => $doctorsHours) {
            foreach ($doctorsHours as $doctorId => $hoursStatus) {
                foreach ($hoursStatus as $hour => $status) {
                    if ($status === 'open') {
                        $isWorkingOnDay[$doctorId][$dayKey] = true;
                    }
                }
            }
        }
        dump($isWorkingOnDay);
        return $this->render('@ProlyfixOnlineCalendar/admin/week_view.html.twig', [
            'days' => $days,
            'hours' => $hours,
            'doctors' => $doctors,
            'isWorkingOnDay' => $isWorkingOnDay,
            'appointmentsByDayDoctorHour' => $appointmentsByDayDoctorHour,
            'slotStatusByDayDoctorHour' => $slotStatusByDayDoctorHour,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'viewMode' => $view,
            'selectedUserId' => $selectedUserId,
            'users' => $users,
            'previousUrl' => $previousUrl,
            'currentUrl' => $currentUrl,
            'nextUrl' => $nextUrl,
            'toggleDayUrl' => $toggleDayUrl,
            'toggleWeekUrl' => $toggleWeekUrl,
        ]);
    }
}
