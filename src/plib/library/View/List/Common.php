<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

use PleskExt\SecurityAdvisor\Helper\Domain;

abstract class Modules_SecurityAdvisor_View_List_Common extends pm_View_List_Simple
{
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

    /**
     * Get Domain certificate info
     *
     * @param pm_Domain $domain
     * @return array
     */
    protected function _getCertificateInfo(\pm_Domain $domain)
    {
        $db = pm_Bootstrap::getDbAdapter();
        $select = $db->select()->from('certificates')
            ->join('hosting', 'hosting.certificate_id = certificates.id')
            ->where('dom_id = ?', $domain->getId());
        $row = $db->fetchRow($select);
        if (!$row || !($cert = urldecode($row['cert'])) || !($ssl = openssl_x509_parse($cert))) {
            return [];
        }

        $validFrom = date("d M Y", $ssl['validFrom_time_t']);
        $validTo = date("d M Y", $ssl['validTo_time_t']);

        $domainName = $domain->getProperty('displayName');
        $san = Modules_SecurityAdvisor_Helper_Ssl::getCertificateSubjects($cert);
        $san = array_filter($san, function ($altName) use ($domainName) {
            return 0 != strcasecmp($altName, $domainName);
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
            'purchase' => $this->_getPurchaseButton($domain->getId(), $status),
            'statusIcon' => $this->_getStatusIcon($status, $certInfo),
            'status' => $status,
            'validFrom' => $validFrom,
            'validTo' => $validTo,
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
        $domainId = intval($domainId);
        $class = ['sw-purchase'];
        if ($status != 'ok') {
            $class[] = 'purchase';
            if ($status == 'letsencrypt') {
                $class[] = 'extended';
            }
        }

        $div = '<div id="sw-purchase:' . $domainId . '" class="' . implode(' ', $class) . '">';
        if ($this->_view->showSymantecPromotion && $status != 'ok') {
            $div .= '<a id="sw-purchase-button:' . $domainId . '"'
                . ' class="sw-purchase-button"'
                . ' href="' . \pm_Context::getBaseUrl() . 'index.php/index/symantec/domain/' . $domainId . '"'
                . ' title="' . $this->lmsg('list.symantec.button.purchaseHint') . '"'
                . (Modules_SecurityAdvisor_Symantec::isInstalled() ? '' : ' onclick="purchaseClick(this, event)"')
                . '>'
                . '<i class="sw-icon-basket"></i>'
                . $this->lmsg('list.symantec.button.purchase' . (in_array('extended', $class) ? 'Extended' : ''))
                . '</a>';
        }
        $div .= '</div>';

        return $div;
    }

    protected function _fetchData()
    {
        $domains = [];
        $pmDomains = Domain::getAllVendorDomains(pm_Session::getClient());
        foreach ($pmDomains as $pmDomain) {
            try {
                $domains[] = $this->_getDomainInfo($pmDomain);
            } catch (pm_Exception $e) {
                continue;
            }
        }

        return array_filter($domains);
    }

    /**
     * Returns domain info
     *
     * @param pm_Domain $domain
     * @return array|null
     * @throws pm_Exception
     */
    private function _getDomainInfo(pm_Domain $domain)
    {
        $webspaceId = $domain->getProperty('webspace_id');

        $domainInfo = [
            'id' => $domain->getId(),
            'domainName' => $domain->getProperty('displayName'),
            'asciiName' => $domain->getName(),
            'certificate' => null,
            'validFrom' => '',
            'validTo' => '',
            'san' => '',
            'webspaceId' => $webspaceId,
        ];

        if (!is_null($this->_subscriptionId) && $domain->getId() != $this->_subscriptionId) {
            return null;
        }

        if (!\pm_Session::getClient()->hasAccessToDomain($domain->getId())) {
            throw new pm_Exception("No access to domain: {$domainInfo['domainName']}");
        }

        if (false !== strpos($domain->getName(), 'xn--')) {
            throw new pm_Exception("IDN domains are not supported: {$domainInfo['domainName']}");
        }

        if (0 === strpos($domain->getName(), '*')) {
            throw new pm_Exception("Wildcard subdomains are not supported: {$domainInfo['domainName']}");
        }

        if ($domain->getProperty('status') != STATUS_ACTIVE) {
            throw new pm_Exception("Domain is not active: {$domainInfo['domainName']}");
        }

        if ($domain->getProperty('htype') != 'vrt_hst') {
            throw new pm_Exception("No hosting for domain: {$domainInfo['domainName']}");
        }

        if ($certInfo = $this->_getCertificateInfo($domain)) {
            $domainInfo = array_merge($domainInfo, $certInfo);
        } else {
            $domainInfo['purchase'] = $this->_getPurchaseButton($domainInfo['id'], 'insecure');
            $domainInfo['status'] = 'insecure';
            $domainInfo['statusIcon'] = $this->_getStatusIcon('insecure');
        }

        if ($this->_showExtendedFilters) {
            $domainInfo['hiddenStatus'] = ($domainInfo['status'] == 'ok' || $domainInfo['status'] == 'letsencrypt') ? 'secure' : 'insecure';
            $domainInfo['hiddenSubscription'] = pm_Domain::getByDomainId($domainInfo['webspaceId'])->getProperty('displayName');

            $domain = pm_Domain::getByDomainId($domainInfo['id']);
            $client = pm_Client::getByClientId($domain->getProperty('cl_id'));
            $domainInfo['hiddenSubscriber'] = $client->getProperty('pname') . ' ' . $client->getProperty('cname');
        }

        return $domainInfo;
    }

    abstract protected function _getTools();
    abstract protected function _getSearchFilters();
}
