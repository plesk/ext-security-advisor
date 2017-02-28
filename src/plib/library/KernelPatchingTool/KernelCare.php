<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH.

class Modules_SecurityAdvisor_KernelPatchingTool_KernelCare implements Modules_SecurityAdvisor_KernelPatchingTool_Interface
{
    const NAME = 'kernelcare-plesk';
    const DISPLAY_NAME = 'KernelCare';
    const DESCRIPTION_LOCALE_KEY = 'controllers.system.kernelcareDescription';
    const LOGO_FILE_NAME = 'logo-kernelcare.png';
    const INSTALL_URL = 'https://ext.plesk.com/packages/04992b06-f95e-4d6b-be49-a466ade680df-kernelcare-plesk/download';

    public function isAvailable()
    {
        return pm_ProductInfo::isUnix();
    }

    public function isInstalled()
    {
        return Modules_SecurityAdvisor_Extension::isInstalled(self::NAME);
    }

    public function isActive()
    {
        return $this->isInstalled();
    }

    public function getName()
    {
        return static::NAME;
    }

    public function getDisplayName()
    {
        return static::DISPLAY_NAME;
    }

    public function getLogoFileName()
    {
        return static::LOGO_FILE_NAME;
    }

    public function getInstallUrl()
    {
        return static::INSTALL_URL;
    }

    public function getDescription()
    {
        return pm_Locale::lmsg(static::DESCRIPTION_LOCALE_KEY);
    }
}
