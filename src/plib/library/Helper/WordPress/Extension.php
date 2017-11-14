<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

use PleskExt\SecurityAdvisor\Helper\Domain;

class Modules_SecurityAdvisor_Helper_WordPress_Extension extends Modules_SecurityAdvisor_Helper_WordPress_Abstract
{
    public function isInstalled()
    {
        return Modules_SecurityAdvisor_WordPress::isInstalled();
    }

    protected function _getInstances()
    {
        return $this->_dbAdapter->query("SELECT * FROM Instances WHERE isIgnored=0");
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
        $count = 0;

        $where = "w.isIgnored=0 AND ((wp.name='url' AND wp.value LIKE '%http://%') OR (wp.name='isAlive' AND wp.value=''))";
        $instances = $this->_getDbAdapter()
            ->fetchAll("SELECT * FROM Instances w INNER JOIN InstanceProperties wp ON wp.instanceId = w.id WHERE $where");


        foreach ($instances as $instance) {
            if (!pm_Session::getClient()->hasAccessToDomain($instance['domainId'])) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    protected function _callWpCli($wordpress, $args)
    {
        return Modules_SecurityAdvisor_WordPress::call('wp-cli', $wordpress['id'], $args);
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
