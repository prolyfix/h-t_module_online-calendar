<?php

namespace Prolyfix\OnlineCalendarBundle\Controller\Admin;

use Prolyfix\HolidayAndTime\Controller\Admin\BaseCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Prolyfix\OnlineCalendarBundle\Entity\PatientAppointment;

class PatientAppointmentCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return PatientAppointment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name'),
            AssociationField::new('patient')->renderAsNativeWidget(),
            AssociationField::new('appointmentType')->renderAsNativeWidget(),
            DateTimeField::new('startDate'),
            DateTimeField::new('endDate'),
            TextField::new('emailAddress')->hideOnIndex(),
            TextField::new('firstName')->hideOnIndex(),
            TextField::new('lastName')->hideOnIndex(),
            TextField::new('phone')->hideOnIndex(),
            TextEditorField::new('description')->hideOnIndex(),
        ];
    }
}
