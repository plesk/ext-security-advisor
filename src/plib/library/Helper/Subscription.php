<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
class Modules_SecurityAdvisor_Helper_Subscription
{
    private $_domainName;
    private $_clientId;
    private $_ftpUser;
    private $_phpHandlerId;

    public function __construct($id)
    {
        $subscription = pm_Bootstrap::getDbAdapter()->fetchRow("SELECT * FROM Subscriptions WHERE id = ?", [$id]);
        if (false === $subscription) {
            throw new pm_Exception("Subscription with id = {$id} not found");
        }
        $request = <<<APICALL
        <webspace>
            <get>
                <filter>
                    <id>{$subscription['object_id']}</id>
                </filter>
                <dataset>
                    <gen_info/>
                    <hosting/>
                </dataset>
            </get>
        </webspace>
APICALL;
        $response = pm_ApiRpc::getService()->call($request);
        if ('error' == $response->webspace->get->result->status) {
            throw new pm_Exception($response->webspace->get->result->errtext);
        }

        $this->_domainName = strval($response->webspace->get->result->data->gen_info->name);
        $this->_clientId = intval($response->webspace->get->result->data->gen_info->{'owner-id'});
        foreach ($response->webspace->get->result->data->hosting->vrt_hst->property as $property) {
            switch ($property->name) {
                case 'ftp_login' :
                    $this->_ftpUser = strval($property->value);
                    break;
                case 'php_handler_id' :
                    $this->_phpHandlerId = strval($property->value);
                    break;
            }
        }
    }

    public function getDomainName()
    {
        return $this->_domainName;
    }

    public function getSysUser()
    {
        return $this->_ftpUser;
    }

    public function getPhpCli()
    {
        $request = <<<APICALL
        <php-handler>
            <get>
                <filter>
                    <id>{$this->_phpHandlerId}</id>
                </filter>
            </get>
        </php-handler>
APICALL;
        $response = pm_ApiRpc::getService()->call($request);
        return strval($response->{'php-handler'}->get->result->clipath);
    }

    public function getPmDomain()
    {
        $request = <<<APICALL
        <site>
            <get>
                <filter>
                    <name>{$this->_domainName}</name>
                </filter>
                <dataset>
                    <gen_info/>
                </dataset>
            </get>
        </site>
APICALL;
        $response = pm_ApiRpc::getService()->call($request);
        return new pm_Domain(intval($response->site->get->result->id));
    }

    public function getPmClient()
    {
        return pm_Client::getByClientId($this->_clientId);
    }

    /**
     * Get current selected subscription if exists
     *
     * @return int|null
     */
    public static function getContextSubscriptionId()
    {
        $context = \Session::get()->subscription();
        if ($context->showAll) {
            return null;
        }

        if ($subscription = $context->getCurrentSubscription()) {
            return $subscription->getDomainId();
        }

        return null;
    }
}
