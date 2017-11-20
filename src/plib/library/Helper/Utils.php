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

    /**
     * Convert idn url to utf8.
     *
     * @param $url
     * @return string
     */
    public static function idnToUtf8($url)
    {
        if (false === strpos($url, 'xn--')) {
            return $url;
        }

        foreach (['https://', 'http://'] as $prefix) {
            if (0 === strpos($url, $prefix)) {
                return $prefix . idn_to_utf8(substr($url, strlen($prefix)));
            }
        }

        return idn_to_utf8($url);
    }
}
