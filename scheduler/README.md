# Online Calendar Scheduler

This folder contains schedule definitions for the Online Calendar module.

## Schedules

1. `send_validated_appointment_reminders.schedule.yaml`
- Frequency: every 5 minutes
- Command: `online-calendar:send-validated-appointment-reminders`

2. `purge_cancelled_appointments.schedule.yaml`
- Frequency: every day at `02:00` (UTC)
- Command: `online-calendar:purge-cancelled-appointments --days=180`

## Runtime classes

The schedule providers are implemented in:

- `src/Scheduler/SendValidatedAppointmentRemindersSchedule.php`
- `src/Scheduler/PurgeCancelledAppointmentsSchedule.php`

These providers use Symfony Scheduler (`#[AsSchedule]`) and dispatch `RunCommandMessage` messages.

## Host app requirements

Ensure the host app has:

- Symfony Scheduler enabled
- Symfony Messenger transport/worker running for scheduled messages
- Cron trigger for scheduler runner (example every minute)

Example cron in host app:

```cron
* * * * * cd /var/www/synpraxis-bundle/holidayAndTime && php bin/console scheduler:consume onlineCalendarSendValidatedAppointmentReminders --time-limit=55 >> var/log/scheduler.log 2>&1
* * * * * cd /var/www/synpraxis-bundle/holidayAndTime && php bin/console scheduler:consume onlineCalendarPurgeCancelledAppointments --time-limit=55 >> var/log/scheduler.log 2>&1
```
