<?php

namespace Prolyfix\OnlineCalendarBundle\Command;

use Prolyfix\OnlineCalendarBundle\Entity\PatientAppointment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'online-calendar:purge-cancelled-appointments',
    description: 'Purges cancelled appointments older than a retention period.',
)]
class PurgeCancelledAppointmentsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'days',
            null,
            InputOption::VALUE_REQUIRED,
            'Delete cancelled appointments older than this number of days.',
            '180'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days = max(1, (int) $input->getOption('days'));
        $threshold = (new \DateTimeImmutable('now'))->modify(sprintf('-%d days', $days));

        $deleted = $this->entityManager->createQueryBuilder()
            ->delete(PatientAppointment::class, 'a')
            ->where('a.status = :status')
            ->andWhere('a.startDate < :threshold')
            ->setParameter('status', PatientAppointment::STATUS_CANCELLED)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();

        $io->success(sprintf(
            'Purged %d cancelled appointments older than %d days.',
            (int) $deleted,
            $days
        ));

        return Command::SUCCESS;
    }
}
