<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

class Modules_SecurityAdvisor_View_List_Subscription extends Modules_SecurityAdvisor_View_List_Common
{
    public function __construct(Zend_View $view, Zend_Controller_Request_Abstract $request, array $options = [])
    {
        if (isset($options['subscriptionId'])) {
            $this->_subscriptionId = $options['subscriptionId'];
            unset($options['subscriptionId']);
        }
        parent::__construct($view, $request, $options);
    }

    protected function _getTools()
    {
        $tools = [];
        if ($this->_isLetsEncryptInstalled) {
            $letsEncryptUrl = pm_Context::getActionUrl('index', 'letsencrypt') . '/subscription/' . $this->_subscriptionId;
            $tools[] = [
                'title' => $this->lmsg('list.domains.letsencryptDomains'),
                'description' => $this->lmsg('list.domains.letsencryptDomainsDescription'),
                'execGroupOperation' => $letsEncryptUrl,
            ];
        } elseif (\pm_Session::getClient()->isAdmin()) {
            $installUrl = pm_Context::getActionUrl('index', 'install-letsencrypt') . '/subscription/' . $this->_subscriptionId;
            $tools[] = [
                'title' => $this->lmsg('list.domains.installLetsencrypt'),
                'description' => $this->lmsg('list.domains.installLetsencryptDescription'),
                'link' => "javascript:Jsw.redirectPost('{$installUrl}')",
            ];
        }
        return $tools;
    }

    protected function _getSearchFilters()
    {
        return [
            'domainName' => [
                'title' => $this->lmsg('list.domains.domainNameColumn'),
                'fields' => ['domainName'],
            ],
            'hiddenStatus' => [
                'title' => $this->lmsg('list.domains.search.status.column'),
                'fields' => ['hiddenStatus'],
                'options' => [
                    '' => $this->lmsg('list.domains.search.status.any'),
                    'insecure' => $this->lmsg('list.domains.search.status.insecure'),
                    'secure' => $this->lmsg('list.domains.search.status.secure'),
                ],
            ],
        ];
    }
}
