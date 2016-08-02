<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
class Modules_SecurityAdvisor_Helper_WordPress
{
    /**
     * @return Modules_SecurityAdvisor_Helper_WordPress_Abstract
     */
    public static function get()
    {
        if (version_compare(pm_ProductInfo::getVersion(), '17.0.15') >= 0) {
            return new Modules_SecurityAdvisor_Helper_WordPress_Extension();
        } else {
            return new Modules_SecurityAdvisor_Helper_WordPress_Plesk();
        }
    }
}
