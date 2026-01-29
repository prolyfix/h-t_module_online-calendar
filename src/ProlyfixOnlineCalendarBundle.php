<?php

namespace Prolyfix\OnlineCalendarBundle;

use Prolyfix\HolidayAndTime\Entity\Module\ModuleConfiguration;
use Prolyfix\HolidayAndTime\Entity\Module\ModuleRight;
use Prolyfix\HolidayAndTime\Module\ModuleBundle;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Security\AuthorizationChecker;
use Prolyfix\CrmBundle\Entity\Appointment;
use Prolyfix\CrmBundle\Entity\Contact;
use Prolyfix\CrmBundle\Entity\ThirdParty;
use Prolyfix\CrmBundle\Entity\ThirdPartyCategory;
use Prolyfix\OnlineCalendarBundle\Entity\PatientAppointment;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProlyfixOnlineCalendarBundle extends ModuleBundle
{
    private $authorizationChecker;

    public function setAuthorizationChecker(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    public static function getTables(): array
    {
        return [
        ];
    }

    const IS_MODULE = true;
    public static function getShortName(): string
    {
        return 'OnlineCalendarBundle';
    }
    public static function getModuleName(): string
    {
        return 'OnlineCalendarBundle';
    }
    public static function getModuleDescription(): string
    {
        return 'Online Calendar Module';
    }
    public static function getModuleType(): string
    {
        return 'module';
    }
    public static function getModuleConfiguration(): array
    {
        return [];
    }

    public static function getModuleRights(): array
    {
        return [];
    }

    public function getMenuConfiguration(): array
    {
         return [];
    }

    public static function getUserConfiguration(): array
    {
        return [
            'patient'=> [
                MenuItem::linkToCrud('PatientenCalendar', 'fas fa-user-injured', PatientAppointment::class),
            ]
        ];
    }

    public static function getModuleAccess(): array
    {
        return [];
    }


}