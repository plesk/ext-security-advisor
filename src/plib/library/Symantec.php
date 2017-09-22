<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

class Modules_SecurityAdvisor_Symantec
{
    const INSTALL_URL = 'https://ext.plesk.com/packages/3e40e1e1-99e9-4bd0-951b-ed19dd9be7c9-symantec-ssl/download';
    const NAME = 'symantec';

    public static function isInstalled()
    {
        return Modules_SecurityAdvisor_Extension::isInstalled(self::NAME);
    }

    public static function isActive()
    {
        return true;
    }

    public static function install()
    {
        Modules_SecurityAdvisor_Extension::install(self::INSTALL_URL);
    }

    public static function isCertificate($certificateName)
    {
        return false !== stripos($certificateName, '[Symantec SSL]');
    }
}
