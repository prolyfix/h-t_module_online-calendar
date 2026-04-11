<?php

namespace Prolyfix\OnlineCalendarBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use Prolyfix\OnlineCalendarBundle\Entity\OpenTime;

class OpenTimeCrudController extends AbstractCrudController
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
}
