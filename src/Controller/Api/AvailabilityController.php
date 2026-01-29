<?php
namespace Prolyfix\OnlineCalendarBundle\Controller\Api;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Prolyfix\OnlineCalendarBundle\Entity\AppointmentCategory;
use Prolyfix\WeekplanningBundle\Entity\Schedule;
use Prolyfix\WeekplanningBundle\Entity\WeekplanLocation;
use Prolyfix\OnlineCalendarBundle\Entity\AppointmentType;
use Prolyfix\WeekplanningBundle\Entity\Room;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AvailabilityController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em) {}

    #[Route('/api/availability', name: 'calendar_availability', methods: ['GET'])]
    public function getAvailability(Request $request): JsonResponse
    {
        $locationId = $request->query->getInt('locationId');
        $isoDate = $request->query->get('date');
        $typeId = $request->query->getInt('typeId');

        if (!$locationId || !$isoDate) {
            return $this->json(['error' => 'locationId and date are required'], 400);
        }

        $date = new DateTimeImmutable($isoDate);
        $weekday = $date->format('l'); // e.g. Monday
        $location = $this->em->getRepository(Room::class)->find($locationId);
        if (!$location) {
            return $this->json(['error' => 'Unknown location'], 404);
        }
        $type = null;
        if ($typeId) {
            $type = $this->em->getRepository(AppointmentCategory::class)->find($typeId);
            if (!$type) {
                return $this->json(['error' => 'Unknown type'], 404);
            }
        }
        $qb = $this->em->getRepository(Schedule::class)->createQueryBuilder('s')
            ->andWhere('s.weeekplanLocation = :loc')
            ->andWhere('s.startingFrom <= :date')
            ->andWhere('s.weekday = :weekday')
            ->setParameter('loc', $location)
            ->setParameter('date', $date)
            ->setParameter('weekday', $weekday);

        $schedules = $qb->getQuery()->getResult();
        $slots = [];
        foreach ($schedules as $schedule) {
            // Basic availability: return schedule windows per room
            $start = $date->setTime((int)$schedule->getTimeFrom()->format('H'), (int)$schedule->getTimeFrom()->format('i'));
            $end = $date->setTime((int)$schedule->getTimeTo()->format('H'), (int)$schedule->getTimeTo()->format('i'));

            $slots[] = [
                'roomId' => $schedule->getRoom()?->getId(),
                'roomName' => $schedule->getRoom()?->getName(),
                'start' => $start->format(DATE_ATOM),
                'end' => $end->format(DATE_ATOM),
                'typeId' => $type?->getId(),
            ];
        }
        return $this->json(['date' => $date->format('Y-m-d'), 'locationId' => $locationId, 'slots' => $slots]);
    }

}
