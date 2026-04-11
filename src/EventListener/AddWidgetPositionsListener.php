<?php

namespace Prolyfix\OnlineCalendarBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Prolyfix\HolidayAndTime\Event\ModifiableArrayEvent;
use Prolyfix\OnlineCalendarBundle\Widget\TodayPatientAppointmentsWidget;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment as Twig;

class AddWidgetPositionsListener
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private Twig $twig
    ) {
    }

    public function onAppConfigureWidgetPositions(ModifiableArrayEvent $event): void
    {
        $availableWidgets = $event->getData();
        $availableWidgets[] = new TodayPatientAppointmentsWidget($this->em, $this->security, $this->twig);
        $event->setData($availableWidgets);
    }
}
