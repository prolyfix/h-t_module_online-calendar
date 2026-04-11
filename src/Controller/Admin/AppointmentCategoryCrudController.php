<?php

namespace Prolyfix\OnlineCalendarBundle\Controller\Admin;

use Prolyfix\HolidayAndTime\Controller\Admin\BaseCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Prolyfix\OnlineCalendarBundle\Entity\AppointmentCategory;
use Prolyfix\OnlineCalendarBundle\Entity\PatientAppointment;

class AppointmentCategoryCrudController extends BaseCrudController
{
    public static function getEntityFqcn(): string
    {
        return AppointmentCategory::class;
    }

}
