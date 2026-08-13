<?php

namespace Prolyfix\OnlineCalendarBundle\Scheduler;

use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('onlineCalendarSendValidatedAppointmentReminders')]
final class SendValidatedAppointmentRemindersSchedule implements ScheduleProviderInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(RecurringMessage::cron('0 12 * * *', new RunCommandMessage('online-calendar:send-validated-appointment-reminders')))
            ->stateful($this->cache);
    }
}
