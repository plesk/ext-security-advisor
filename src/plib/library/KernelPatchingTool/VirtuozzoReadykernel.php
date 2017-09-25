<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH.

class Modules_SecurityAdvisor_KernelPatchingTool_VirtuozzoReadykernel implements Modules_SecurityAdvisor_KernelPatchingTool_Interface
{
    const NAME = 'readykernel';
    const DISPLAY_NAME = 'Virtuozzo ReadyKernel';
    const DESCRIPTION_LOCALE_KEY = 'controllers.system.virtuozzoReadyKernelEOL';
    const DESCRIPTION_LINKTEXT_LOCALE_KEY = 'controllers.system.virtuozzoReadKernelEOL.linktext';
    const LOGO_FILE_NAME = 'logo-virtuozzo-readykernel.png';
    const INSTALL_URL = 'https://ext.plesk.com/packages/1b917d8b-72e8-412d-acd7-8e863c30068c-readykernel/download';
    const EOL_ANNOUNCE_URL = 'https://help.virtuozzo.com/customer/portal/articles/2878194';

    public function isAvailable()
    {
        return false;
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
        return \pm_Locale::lmsg(static::DESCRIPTION_LOCALE_KEY, [
            'link' => '<a href="' . static::EOL_ANNOUNCE_URL . '" target="_blank">'
                . \pm_Locale::lmsg(static::DESCRIPTION_LINKTEXT_LOCALE_KEY)
                . '</a>',
        ]);
    }
}
