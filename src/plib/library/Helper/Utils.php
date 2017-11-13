<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

class Modules_SecurityAdvisor_Helper_Utils
{
    public static function getOsVersion()
    {
        return method_exists('pm_ProductInfo', 'getOsVersion')
            ? \pm_ProductInfo::getOsVersion()
            : php_uname('r');
    }
}
