<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

class Modules_SecurityAdvisor_CustomButtons extends pm_Hook_CustomButtons
{
    public function getButtons()
    {
        if (!\pm_Session::getClient()->isAdmin() || version_compare(\pm_ProductInfo::getVersion(), '17.0') < 0) {
            return [];
        }

        return [
            [
                'place' => self::PLACE_DOMAIN,
                'title' => \pm_Locale::lmsg('custom.button.title'),
                'description' => \pm_Locale::lmsg('custom.button.description'),
                'icon' => \pm_Context::getBaseUrl() . 'images/home-promo.png',
                'link' => \pm_Context::getBaseUrl() . 'index.php/index/subscription',
            ],
        ];
    }
}
