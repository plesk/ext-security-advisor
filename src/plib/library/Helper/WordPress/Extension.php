<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
class Modules_SecurityAdvisor_Helper_WordPress_Extension extends Modules_SecurityAdvisor_Helper_WordPress_Abstract
{
    public function isInstalled()
    {
        return Modules_SecurityAdvisor_WordPress::isInstalled();
    }

    protected function _getInstances()
    {
        return $this->_dbAdapter->query("SELECT * FROM Instances");
    }

    protected function _getInstance($wpId)
    {
        return $this->_dbAdapter->fetchRow("SELECT * FROM Instances WHERE id = ?", [$wpId]);
    }

    protected function _getInstanceProperties($wpId)
    {
        return $this->_dbAdapter->query("SELECT * FROM InstanceProperties WHERE instanceId = ?", [$wpId]);
    }

    protected function _getNotSecureCount()
    {
        $where = "wp.value LIKE '%http://%'";

        $client = pm_Session::getClient();
        if (!$client->isAdmin()) {
            $domainIds = Modules_SecurityAdvisor_Helper_WordPress::getAllVendorDomainIds($client->getId());
            $domainIds = implode(',', $domainIds);
            $where .= " AND domainId IN ($domainIds)";
        }

        return $this->_dbAdapter->fetchOne("SELECT count(*) FROM Instances w
            INNER JOIN InstanceProperties wp ON (wp.instanceId = w.id AND wp.name = 'url')
            WHERE $where");
    }

    protected function _callWpCli($wordpress, $args)
    {
        Modules_SecurityAdvisor_WordPress::call('wp-cli', $wordpress['id'], $args);
    }

    protected function _resetCache($wpId)
    {
        Modules_SecurityAdvisor_WordPress::call('clear-cache', $wpId);
    }

    protected function _getDbAdapter()
    {
        return Modules_SecurityAdvisor_WordPress::getDbAdapter();
    }
}
