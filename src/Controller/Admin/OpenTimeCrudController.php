<?php

namespace Prolyfix\OnlineCalendarBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Prolyfix\HolidayAndTime\Controller\Admin\BaseCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use Prolyfix\OnlineCalendarBundle\Entity\OpenTime;
use Symfony\Component\HttpFoundation\Response;

class OpenTimeCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return OpenTime::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Open Time')
            ->setEntityLabelInPlural('Open Times')
            ->setSearchFields(['weekday'])
            ->setDefaultSort(['weekday' => 'ASC', 'startTime' => 'ASC']);
    }

    public function configureFields(string $pageName): iterable
    {
        $weekdayChoices = [
            'Monday' => 'Monday',
            'Tuesday' => 'Tuesday',
            'Wednesday' => 'Wednesday',
            'Thursday' => 'Thursday',
            'Friday' => 'Friday',
            'Saturday' => 'Saturday',
            'Sunday' => 'Sunday',
        ];

        yield IdField::new('id')->onlyOnIndex();
        yield ChoiceField::new('weekday')
            ->setChoices($weekdayChoices)
            ->renderExpanded(false)
            ->renderAsBadges(false);
        yield TimeField::new('startTime');
        yield TimeField::new('endTime');
        yield TimeField::new('breakFrom')->hideOnIndex();
        yield TimeField::new('breakTo')->hideOnIndex();
        yield AssociationField::new('user');
        yield AssociationField::new('room');
    }

    public function moduleConfigurationTab(EntityManagerInterface $em): Response
    {
        $this->assertListAccessForEntity(OpenTime::class, 'You are not allowed to view open times.');

        $company = $this->getUser()?->getCompany();
        $criteria = [];
        if ($company !== null) {
            $criteria['tenant'] = $company;
        }

        $openTimes = $em->getRepository(OpenTime::class)->findBy($criteria, ['weekday' => 'ASC']);

        $newUrl = (clone $this->adminUrlGenerator)
            ->setController(self::class)
            ->setAction('new')
            ->unset('entityId')
            ->generateUrl();

        $manageUrl = (clone $this->adminUrlGenerator)
            ->setController(self::class)
            ->setAction('index')
            ->unset('entityId')
            ->generateUrl();

        $rows = '';
        foreach ($openTimes as $openTime) {
            $editUrl = (clone $this->adminUrlGenerator)
                ->setController(self::class)
                ->setAction('edit')
                ->setEntityId($openTime->getId())
                ->generateUrl();

            $weekday = htmlspecialchars((string) ($openTime->getWeekday() ?? ''), ENT_QUOTES, 'UTF-8');
            $start = $openTime->getStartTime()?->format('H:i') ?? '-';
            $end = $openTime->getEndTime()?->format('H:i') ?? '-';

            $rows .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s</td><td><a class="btn btn-sm btn-secondary" href="%s">Edit</a></td></tr>',
                $weekday,
                $start,
                $end,
                htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8')
            );
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="4" class="text-muted">No open times yet.</td></tr>';
        }

        $html = sprintf(
            '<div class="card"><div class="card-body">'
            . '<div class="d-flex gap-2 mb-3">'
            . '<a class="btn btn-primary" href="%s">Create open time</a>'
            . '<a class="btn btn-outline-secondary" href="%s">Manage open times (create/edit/delete)</a>'
            . '</div>'
            . '<div class="table-responsive"><table class="table">'
            . '<thead><tr><th>Weekday</th><th>Start</th><th>End</th><th>Actions</th></tr></thead>'
            . '<tbody>%s</tbody></table></div>'
            . '</div></div>',
            htmlspecialchars($newUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8'),
            $rows
        );

        return new Response($html);
    }
}
