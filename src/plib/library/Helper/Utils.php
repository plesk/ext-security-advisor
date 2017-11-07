<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

class Modules_SecurityAdvisor_Helper_Utils
{
    public static function countInsecureDomains($webspaceId = 0)
    {
        if ($webspaceId) {
            return \pm_Bootstrap::getDbAdapter()->fetchOne(
                'SELECT COUNT(*) FROM hosting h JOIN domains d ON d.id = h.dom_id'
                . ' WHERE (id = ? OR webspace_id = ?) AND h.certificate_id = 0',
                [$webspaceId, $webspaceId]
            );
        } else {
            return \pm_Bootstrap::getDbAdapter()->fetchOne('SELECT COUNT(*) FROM hosting WHERE certificate_id = 0');
        }
    }

    public static function getOsVersion()
    {
        return method_exists('pm_ProductInfo', 'getOsVersion')
            ? \pm_ProductInfo::getOsVersion()
            : php_uname('r');
    }
}
