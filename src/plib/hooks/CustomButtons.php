<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

require_once __DIR__ . '/../vendor/autoload.php';

use PleskExt\SecurityAdvisor\Helper\Permission;

class Modules_SecurityAdvisor_CustomButtons extends pm_Hook_CustomButtons
{
    public function getButtons()
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $commonParams = [
            'title' => \pm_Locale::lmsg('custom.button.title'),
            'description' => \pm_Locale::lmsg('custom.button.home.description'),
            'icon' => \pm_Context::getBaseUrl() . 'images/nav-security-advisor.png',
            'link' => \pm_Context::getBaseUrl()
        ];

        return [
            array_merge(
                $commonParams, [
                    'place' => self::PLACE_DOMAIN,
                    'description' => \pm_Locale::lmsg('custom.button.description'),
                    'icon' => \pm_Context::getBaseUrl() . 'images/home-promo.png',
                    'link' => \pm_Context::getActionUrl('index', 'domain-list'),
                    'visibility' => [$this, 'isClientButtonVisible'],
                ]
            ),
            array_merge(
                $commonParams, [
                    'place' => self::PLACE_ADMIN_NAVIGATION,
                ]
            ),
            array_merge(
                $commonParams, [
                    'place' => self::PLACE_RESELLER_NAVIGATION,
                ]
            ),
            array_merge(
                $commonParams, [
                    'place' => self::PLACE_HOSTING_PANEL_TABS,
                    'link' => \pm_Context::getActionUrl('index', 'domain-list'),
                    'styleClass' => 'nav-advisor',
                    'visibility' => [$this, 'isClientButtonVisible'],
                ]
            ),
        ];
    }

    public function isAvailable()
    {
        return version_compare(\pm_ProductInfo::getVersion(), '17.0') >= 0;
    }

    public function isClientButtonVisible()
    {
        return Permission::hasAccessToSomeDomain();
    }
}
