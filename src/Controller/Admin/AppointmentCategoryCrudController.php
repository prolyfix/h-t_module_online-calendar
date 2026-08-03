<?php

namespace Prolyfix\OnlineCalendarBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Prolyfix\HolidayAndTime\Controller\Admin\BaseCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Prolyfix\OnlineCalendarBundle\Entity\AppointmentCategory;
use Prolyfix\OnlineCalendarBundle\Entity\PatientAppointment;
use Symfony\Component\HttpFoundation\Response;

class AppointmentCategoryCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return AppointmentCategory::class;
    }

    public function moduleConfigurationTab(EntityManagerInterface $em): Response
    {
        $this->assertListAccessForEntity(AppointmentCategory::class, 'You are not allowed to view appointment categories.');

        $company = $this->getUser()?->getCompany();
        $criteria = [];
        if ($company !== null) {
            $criteria['tenant'] = $company;
        }

        $categories = $em->getRepository(AppointmentCategory::class)->findBy($criteria, ['name' => 'ASC']);

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
        foreach ($categories as $category) {
            $editUrl = (clone $this->adminUrlGenerator)
                ->setController(self::class)
                ->setAction('edit')
                ->setEntityId($category->getId())
                ->generateUrl();

            $name = htmlspecialchars((string) ($category->getName() ?? ''), ENT_QUOTES, 'UTF-8');
            $duration = $category->getDurationMinutes();

            $rows .= sprintf(
                '<tr><td>%s</td><td>%d min</td><td><a class="btn btn-sm btn-secondary" href="%s">Edit</a></td></tr>',
                $name,
                $duration,
                htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8')
            );
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="3" class="text-muted">No appointment categories yet.</td></tr>';
        }

        $html = sprintf(
            '<div class="card"><div class="card-body">'
            . '<div class="d-flex gap-2 mb-3">'
            . '<a class="btn btn-primary" href="%s">Create appointment category</a>'
            . '<a class="btn btn-outline-secondary" href="%s">Manage appointment categories (create/edit/delete)</a>'
            . '</div>'
            . '<div class="table-responsive"><table class="table">'
            . '<thead><tr><th>Name</th><th>Duration</th><th>Actions</th></tr></thead>'
            . '<tbody>%s</tbody></table></div>'
            . '</div></div>',
            htmlspecialchars($newUrl, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($manageUrl, ENT_QUOTES, 'UTF-8'),
            $rows
        );

        return new Response($html);
    }

}
