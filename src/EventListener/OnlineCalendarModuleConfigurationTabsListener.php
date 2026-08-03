<?php

namespace Prolyfix\OnlineCalendarBundle\EventListener;

use Prolyfix\HolidayAndTime\Event\ModifiableArrayEvent;
use Prolyfix\OnlineCalendarBundle\ProlyfixOnlineCalendarBundle;

class OnlineCalendarModuleConfigurationTabsListener
{
    public function onAppShowModuleConfigurationTabs(ModifiableArrayEvent $event): void
    {
        $payload = $event->getData();
        $module = $payload['module'] ?? null;
        if (!$module instanceof ProlyfixOnlineCalendarBundle) {
            return;
        }

        $tabs = $payload['tabs'] ?? [];
        if (method_exists($module, 'getModuleConfigurationTabs')) {
            $moduleTabs = $module::getModuleConfigurationTabs();
            if (is_array($moduleTabs)) {
                foreach ($moduleTabs as $key => $tab) {
                    $tabs[$key] = $tab;
                }
            }
        }

        $payload['tabs'] = $tabs;
        $event->setData($payload);
    }
}
