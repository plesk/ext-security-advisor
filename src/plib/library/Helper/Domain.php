<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

namespace PleskExt\SecurityAdvisor\Helper;

class Domain
{
    /**
     * @param \pm_Domain $domain
     * @return string
     */
    public static function getDomainOverviewUrl(\pm_Domain $domain)
    {
        $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view = $viewRenderer->view;
        $view->addHelperPath('pm/View/Helper', 'pm_View_Helper');
        return $view->domainOverviewUrl($domain);
    }

    /**
     * Return all vendor domains (own + customer`s)
     *
     * @param \pm_Client $client
     * @return array
     */
    public static function getAllVendorDomains(\pm_Client $client)
    {
        if ($client->isAdmin()) {
            $domains = \Db_Table_Broker::get('domains')->fetchAll();
        } else {
            $clientId = $client->getId();
            $domains = \Db_Table_Broker::get('domains')
                ->fetchAll("cl_id=$clientId OR vendor_id=$clientId");
        }

        return array_map(function ($domain) {
            return new \pm_Domain($domain['id']);
        }, $domains->toArray());
    }

    /**
     * Return all vendor domains ids (own + customer`s)
     *
     * @param \pm_Client $client
     * @return array
     */
    public static function getAllVendorDomainsIds(\pm_Client $client)
    {
        $pmDomains = static::getAllVendorDomains($client);

        return array_map(function ($pmDomain) {
            return $pmDomain->getId();
        }, $pmDomains);
    }
}
