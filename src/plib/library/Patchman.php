<?php

class Modules_SecurityWizard_Patchman
{
    const INSTALL_URL = 'https://ext.plesk.com/packages/6a51a3d4-ba72-4820-96bc-305e2a72bccc-patchmaninstaller/download';
    const NAME = 'patchmaninstaller';

    public static function isInstalled()
    {
    return Modules_SecurityWizard_Extension::isInstalled(self::NAME);
    }

    public static function isActive()
    {
    return true;
    }

    public static function install()
    {
    return Modules_SecurityWizard_Extension::install(self::INSTALL_URL);
    }

}
