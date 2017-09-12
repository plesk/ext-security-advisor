<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

abstract class Modules_SecurityAdvisor_View_List_Common extends pm_View_List_Simple
{
    const API_GET_WEBSPACES = <<<GETALLWEBSPACES
<webspace>
    <get>
        <filter></filter>
        <dataset><gen_info/><hosting/></dataset>
    </get>
</webspace>
GETALLWEBSPACES;

    const API_GET_SITES = <<<GETALLSITES
<site>
    <get>
        <filter></filter>
        <dataset><gen_info/><hosting/></dataset>
    </get>
</site>
GETALLSITES;

    protected $_isLetsEncryptInstalled;
    protected $_showExtendedFilters = false;
    protected $_subscriptionId = null;

    public function __construct(Zend_View $view, Zend_Controller_Request_Abstract $request, array $options = [])
    {
        if (isset($options['showExtendedFilters'])) {
            $this->_showExtendedFilters = $options['showExtendedFilters'];
            unset($options['showExtendedFilters']);
        }
        parent::__construct($view, $request, $options);
    }

    protected function _init()
    {
        parent::_init();

        $this->_isLetsEncryptInstalled = Modules_SecurityAdvisor_Letsencrypt::isInstalled();
        $this->_view->isSymantecInstalled = Modules_SecurityAdvisor_Symantec::isInstalled();
        $this->_view->baseUrl = \pm_Context::getBaseUrl();

        $this->setData($this->_fetchData());
        $this->setColumns($this->_getColumns());
        $this->setTools($this->_getTools());

        if ($this->_showExtendedFilters) {
            $this->addSearchFilters($this->_getSearchFilters());
        }
    }

    private function _getColumns()
    {
        $columns = [
            self::COLUMN_SELECTION,
            'domainName' => [
                'title' => $this->lmsg('list.domains.domainNameColumn'),
            ],
            'purchase' => [
                'title' => '',
                'noEscape' => true,
                'sortable' => false,
            ],
            'statusIcon' => [
                'title' => $this->lmsg('list.domains.statusColumn'),
                'noEscape' => true,
            ],
            'validFrom' => [
                'title' => $this->lmsg('list.domains.validFromColumn'),
            ],
            'validTo' => [
                'title' => $this->lmsg('list.domains.validToColumn'),
            ],
            'san' => [
                'title' => $this->lmsg('list.domains.sanColumn'),
            ],
        ];

        if (!$this->_showExtendedFilters) {
            $columns['domainName']['searchable'] = true;
        }

        return $columns;
    }

    protected function _getCertificateInfo($domainInfo)
    {
        $db = pm_Bootstrap::getDbAdapter();
        $select = $db->select()->from('certificates')
            ->join('hosting', 'hosting.certificate_id = certificates.id')
            ->where('dom_id = ?', $domainInfo['id']);
        $row = $db->fetchRow($select);
        if (!$row || !($cert = urldecode($row['cert'])) || !($ssl = openssl_x509_parse($cert))) {
            return [];
        }

        $san = Modules_SecurityAdvisor_Helper_Ssl::getCertificateSubjects($cert);
        $san = array_filter($san, function ($altName) use ($domainInfo) {
            return 0 != strcasecmp($altName, $domainInfo['domainName']);
        });

        $certInfo = '';
        $certData = ($row['ca_cert'] ? urldecode($row['ca_cert']) . "\n" : "") . $cert;
        if (!Modules_SecurityAdvisor_Helper_Ssl::verifyCertificate($certData)) {
            $status = 'invalid';
        } elseif (Modules_SecurityAdvisor_Letsencrypt::isCertificate($row['name'])) {
            $status = 'letsencrypt';
            $ssl = openssl_x509_parse($certData);
            $certInfo = $ssl['subject']['CN'];
        } else {
            $status = 'ok';
            if (Modules_SecurityAdvisor_Symantec::isCertificate($row['name'])) {
                $ssl = openssl_x509_parse($certData);
                $certInfo = $ssl['subject']['CN'];
            }
        }
        return [
            'purchase' => $this->_getPurchaseButton($domainInfo['id'], $status),
            'statusIcon' => $this->_getStatusIcon($status, $certInfo),
            'status' => $status,
            'validFrom' => date("d M Y", $ssl['validFrom_time_t']),
            'validTo' => date("d M Y", $ssl['validTo_time_t']),
            'san' => implode(', ', $san),
        ];
    }

    protected function _getStatusIcon($status, $info = '')
    {
        $url = pm_Context::getBaseUrl() . "images/ssl-{$status}.png";
        $title = $this->lmsg('list.domains.status' . ucfirst($status)) . ($info ? "\n\"" . $this->_view->escape($info) . '"' : '');
        return '<img src="' . $this->_view->escape($url) . '"'
            . ' alt="' . $this->_view->escape($status) . '"'
            . ' title="' . $this->_view->escape($title) . '"/>';
    }

    /**
     * @param int $domainId
     * @param string $status
     * @return string
     */
    protected function _getPurchaseButton($domainId, $status)
    {
        $class = ['sw-purchase'];
        if ($status != 'ok') {
            $class[] = 'purchase';
            if ($status == 'letsencrypt') {
                $class[] = 'extended';
            }
        }

        return '<div id="sw-purchase:' . intval($domainId) . '" class="' . implode(' ', $class) . '"></div>';
    }

    protected function _fetchData()
    {
        $domains = [];
        foreach (pm_ApiRpc::getService()->call(static::API_GET_WEBSPACES)->webspace->get->result as $result) {
            try {
                $domains[] = $this->_getDomainFromXml($result);
            } catch (pm_Exception $e) {
                continue;
            }
        }
        foreach (pm_ApiRpc::getService()->call(static::API_GET_SITES)->site->get->result as $result) {
            try {
                $domains[] = $this->_getDomainFromXml($result);
            } catch (pm_Exception $e) {
                continue;
            }
        }

        return array_filter($domains);
    }

    private function _getDomainFromXml($result)
    {
        if ('ok' != $result->status) {
            throw new pm_Exception($result->errtext);
        }
        if (!$result->id) {
            throw new pm_Exception('Object not found');
        }

        $domainId = intval($result->id);
        $webspaceId = intval($result->data->gen_info->{'webspace-id'}) ?: $domainId;

        $domainInfo = [
            'id' => $domainId,
            'domainName' => strval($result->data->gen_info->name),
            'asciiName' => strval($result->data->gen_info->{'ascii-name'}),
            'certificate' => null,
            'validFrom' => '',
            'validTo' => '',
            'san' => '',
            'webspaceId' => $webspaceId,
        ];

        if (!is_null($this->_subscriptionId) && $webspaceId != $this->_subscriptionId) {
            return null;
        }

        if (!\pm_Session::getClient()->hasAccessToDomain($domainId)) {
            throw new pm_Exception("No access to domain: {$domainInfo['domainName']}");
        }

        if (0 === strpos($domainInfo['domainName'], '*')) {
            throw new pm_Exception("Wildcard subdomains are not supported: {$domainInfo['domainName']}");
        }

        if (false !== strpos($domainInfo['asciiName'], 'xn--')) {
            throw new pm_Exception("IDN domains are not supported: {$domainInfo['domainName']}");
        }

        if (0 != intval($result->data->gen_info->status)) {
            throw new pm_Exception("Domain is not active: {$domainInfo['domainName']}");
        }

        if (!isset($result->data->hosting->vrt_hst)) {
            throw new pm_Exception("No hosting for domain: {$domainInfo['domainName']}");
        }

        foreach ($result->data->hosting->vrt_hst->property as $property) {
            if ('certificate_name' == $property->name) {
                $domainInfo['certificate'] = strval($property->value);
            }
        }

        if ($domainInfo['certificate']) {
            $domainInfo = array_merge($domainInfo, $this->_getCertificateInfo($domainInfo));
        } else {
            $domainInfo['purchase'] = $this->_getPurchaseButton($domainInfo['id'], 'insecure');
            $domainInfo['status'] = 'insecure';
            $domainInfo['statusIcon'] = $this->_getStatusIcon('insecure');
        }

        if ($this->_showExtendedFilters) {
            $domainInfo['hiddenStatus'] = ($domainInfo['status'] == 'ok' || $domainInfo['status'] == 'letsencrypt') ? 'secure' : 'insecure';
            $domainInfo['hiddenSubscription'] = pm_Domain::getByDomainId($domainInfo['webspaceId'])->getDisplayName();

            $domain = pm_Domain::getByDomainId($domainInfo['id']);
            $client = pm_Client::getByClientId($domain->getProperty('cl_id'));
            $domainInfo['hiddenSubscriber'] = $client->getProperty('pname') . ' ' . $client->getProperty('cname');
        }

        return $domainInfo;
    }

    abstract protected function _getTools();
    abstract protected function _getSearchFilters();
}
