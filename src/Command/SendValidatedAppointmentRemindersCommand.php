<?php

namespace Prolyfix\OnlineCalendarBundle\Command;

use DateTimeImmutable;
use Prolyfix\OnlineCalendarBundle\Entity\PatientAppointment;
use Prolyfix\OnlineCalendarBundle\Repository\PatientAppointmentRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'online-calendar:send-validated-appointment-reminders',
    description: 'Sends reminders for tomorrow\'s validated appointments.',
)]
class SendValidatedAppointmentRemindersCommand extends Command
{
    public function __construct(
        private readonly PatientAppointmentRepository $appointmentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $tomorrowStart = new DateTimeImmutable('tomorrow 00:00:00');
        $tomorrowEnd = $tomorrowStart->modify('+1 day');

        $appointments = $this->appointmentRepository->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.startDate >= :start')
            ->andWhere('a.startDate < :end')
            ->andWhere('a.reminderSentAt IS NULL')
            ->setParameter('status', PatientAppointment::STATUS_VALIDATED)
            ->setParameter('start', $tomorrowStart)
            ->setParameter('end', $tomorrowEnd)
            ->getQuery()
            ->getResult();

        if (count($appointments) === 0) {
            $io->success('No validated appointments for tomorrow without reminders.');
            return Command::SUCCESS;
        }

        $fromEmail = (string) ($this->params->get('email_sender') ?? 'noreply@example.com');
        $fromName = (string) ($this->params->get('email_sender_name') ?? 'Synstitute');

        $sent = 0;
        $skipped = 0;

        foreach ($appointments as $appointment) {
            if (!$appointment instanceof PatientAppointment) {
                continue;
            }

            $recipient = $appointment->getEmailAddress() ?: $appointment->getPatient()?->getEmail();
            if ($recipient === null || $recipient === '') {
                $skipped++;
                continue;
            }

            try {
                $patientName = trim((string) (
                    $appointment->getPatient()?->getFirstName() . ' ' . $appointment->getPatient()?->getLastName()
                ));

                if ($patientName === '') {
                    $patientName = trim((string) ($appointment->getFirstName() . ' ' . $appointment->getLastName()));
                }

                if ($patientName === '') {
                    $patientName = 'Patient';
                }

                $start = $appointment->getStartDate();
                $startText = $start ? $start->format('d.m.Y H:i') : '-';

                $email = (new Email())
                    ->from(new Address($fromEmail, $fromName))
                    ->to($recipient)
                    ->subject('Appointment reminder for tomorrow')
                    ->text(sprintf(
                        "Hello %s,\n\nthis is a reminder for your appointment tomorrow at %s.\n\nBest regards,\n%s",
                        $patientName,
                        $startText,
                        $fromName
                    ));

                $this->mailer->send($email);
                $appointment->setReminderSentAt(new \DateTime());
                $sent++;
            } catch (\Throwable $exception) {
                $io->warning(sprintf(
                    'Failed sending reminder for appointment #%d: %s',
                    $appointment->getId() ?? 0,
                    $exception->getMessage()
                ));
                $skipped++;
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Reminders sent: %d, skipped: %d', $sent, $skipped));

        return Command::SUCCESS;
    }
}
