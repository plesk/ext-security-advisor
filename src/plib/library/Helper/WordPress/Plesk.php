<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

use PleskExt\SecurityAdvisor\Helper\Domain;

class Modules_SecurityAdvisor_Helper_WordPress_Plesk extends Modules_SecurityAdvisor_Helper_WordPress_Abstract
{
    protected function _getInstances()
    {
        return $this->_dbAdapter->query("SELECT subscriptionId domainId, WordpressInstances.* FROM WordpressInstances WHERE isIgnored=0");
    }

    protected function _getInstance($wpId)
    {
        return $this->_dbAdapter->fetchRow("SELECT * FROM WordpressInstances WHERE id = ?", [$wpId]);
    }

    protected function _getInstanceProperties($wpId)
    {
        return $this->_dbAdapter->query("SELECT * FROM WordpressInstanceProperties WHERE wordpressInstanceId = ?", [$wpId]);
    }

    protected function _getNotSecureCount()
    {
        $count = 0;

        $where = "w.isIgnored=0 AND ((wp.name='url' AND wp.value LIKE '%http://%') OR (wp.name='isAlive' AND wp.value=''))";
        $instances = $this->_getDbAdapter()
            ->fetchAll("SELECT * FROM WordpressInstances w INNER JOIN WordpressInstanceProperties wp ON wp.wordpressInstanceId = w.id WHERE $where");


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
        $subscription = new Modules_SecurityAdvisor_Helper_Subscription($wordpress['subscriptionId']);
        $fileManager = new pm_FileManager($subscription->getPmDomain()->getId());

        $res = pm_ApiCli::callSbin('wpmng', array_merge([
            '--user=' . $subscription->getSysUser(),
            '--php=' . $subscription->getPhpCli(),
            '--',
            '--path=' . $fileManager->getFilePath($wordpress['path']),
        ], $args));

        if (0 !== $res['code']) {
            throw new pm_Exception($res['stdout'] . $res['stderr']);
        }

        return $res;
    }

    protected function _resetCache($wpId)
    {
        $request = <<<APICALL
        <wp-instance>
            <clear-cache>
                <filter>
                    <id>{$wpId}</id>
                </filter>
            </clear-cache>
        </wp-instance>
APICALL;
        $response = pm_ApiRpc::getService()->call($request);
        if ('error' == $response->{'wp-instance'}->{'clear-cache'}->result->status) {
            throw new pm_Exception($response->{'wp-instance'}->{'clear-cache'}->result->errtext);
        }
    }

    protected function _getDbAdapter()
    {
        return pm_Bootstrap::getDbAdapter();
    }
}
