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
}
