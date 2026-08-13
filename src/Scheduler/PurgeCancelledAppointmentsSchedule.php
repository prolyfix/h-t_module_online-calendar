<?php

namespace Prolyfix\OnlineCalendarBundle\Scheduler;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('onlineCalendarPurgeCancelledAppointments')]
final class PurgeCancelledAppointmentsSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(RecurringMessage::cron('0 2 * * *', new RunCommandMessage('online-calendar:purge-cancelled-appointments --days=180')))
            ->stateful($this->cache);
    }
}
