<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH.

class Modules_SecurityAdvisor_KernelPatchingTool_VirtuozzoReadykernel implements Modules_SecurityAdvisor_KernelPatchingTool_Interface
{
    const NAME = 'readykernel';
    const DISPLAY_NAME = 'Virtuozzo ReadyKernel';
    const DESCRIPTION_LOCALE_KEY = 'controllers.system.virtuozzoReadyKernelDescription';
    const LOGO_FILE_NAME = 'logo-virtuozzo-readykernel.png';
    const INSTALL_URL = 'https://ext.plesk.com/packages/1b917d8b-72e8-412d-acd7-8e863c30068c-readykernel/download';

    public function isAvailable()
    {
        if (!pm_ProductInfo::isUnix()) {
            return false;
        }
        try {
            $pleskVersionData = explode('.', pm_ProductInfo::getVersion());
            if (17 > intval($pleskVersionData[0])) {
                // module Virtuozzo ReadyKernel supported only on Plesk 17.0 and up
                return false;
            }
            $virtualization = pm_ProductInfo::getVirtualization();
            if (pm_ProductInfo::VIRT_VZ == $virtualization || pm_ProductInfo::VIRT_OPENVZ == $virtualization) {
                return false;
            }
            if ('CentOS' == pm_ProductInfo::getOsName() && 7 <= pm_ProductInfo::getOsShortVersion()) {
                return true;
            }
        } catch (Exception $e) {
            // do not fail in case of any error, just put it into log
            pm_Log::err('Unable to check ability to install Virtuozzo ReadyKernel module: ' . $e->getMessage());
            return false;
        }
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
        return pm_Locale::lmsg(static::DESCRIPTION_LOCALE_KEY);
    }
}
