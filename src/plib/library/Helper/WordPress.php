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

    /**
     * Return all vendor domains ids (own + customer`s)
     *
     * @param $clientId
     * @return array
     */
    public static function getAllVendorDomainIds($clientId)
    {
        $domains = Db_Table_Broker::get('domains')
            ->fetchAll("cl_id=$clientId OR vendor_id=$clientId");

        return array_map(function ($domain) {
            return $domain['id'];
        }, $domains->toArray());
    }
}
