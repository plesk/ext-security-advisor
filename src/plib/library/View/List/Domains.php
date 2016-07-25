<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
class Modules_SecurityAdvisor_View_List_Domains extends pm_View_List_Simple
{
    private $_isLetsEncryptInstalled;

    protected function _init()
    {
        parent::_init();

        $this->_isLetsEncryptInstalled = Modules_SecurityAdvisor_Letsencrypt::isInstalled();
        $this->setData($this->_fetchData());
        $this->setColumns($this->_getColumns());
        $this->setTools($this->_getTools());
    }

    private function _fetchData()
    {
        $getWebspaces = <<<GETALLWEBSPACES
<webspace>
    <get>
        <filter></filter>
        <dataset><gen_info/><hosting/></dataset>
    </get>
</webspace>
GETALLWEBSPACES;

        $getSites = <<<GETALLSITES
<site>
    <get>
        <filter></filter>
        <dataset><gen_info/><hosting/></dataset>
    </get>
</site>
GETALLSITES;

        $domains = [];
        foreach (pm_ApiRpc::getService()->call($getWebspaces)->webspace->get->result as $result) {
            try {
                $domains[] = $this->_getDomainFromXml($result);
            } catch (pm_Exception $e) {
                continue;
            }
        }
        foreach (pm_ApiRpc::getService()->call($getSites)->site->get->result as $result) {
            try {
                $domains[] = $this->_getDomainFromXml($result);
            } catch (pm_Exception $e) {
                continue;
            }
        }
        return $domains;
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
        $domainInfo = [
            'id' => $domainId,
            'domainName' => strval($result->data->gen_info->name),
            'asciiName' => strval($result->data->gen_info->{'ascii-name'}),
            'certificate' => null,
            'validFrom' => '',
            'validTo' => '',
            'san' => '',
        ];
        if ($webspaceId = $result->data->gen_info->{"webspace-id"}) {
            $domainInfo['webspaceId'] = intval($webspaceId);
        } else {
            $domainInfo['webspaceId'] = $domainId;
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
            $domainInfo['status'] = $this->_getStatusIcon('insecure');
        }
        return $domainInfo;
    }

    private function _getCertificateInfo($domainInfo)
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

        $certData = ($row['ca_cert'] ? urldecode($row['ca_cert']) . "\n" : "") . $cert;
        if (!Modules_SecurityAdvisor_Helper_Ssl::verifyCertificate($certData)) {
            $status = 'invalid';
        } elseif (Modules_SecurityAdvisor_Letsencrypt::isCertificate($row['name'])) {
            $status = 'letsencrypt';
        } else {
            $status = 'ok';
        }
        return [
            'status' => $this->_getStatusIcon($status),
            'validFrom' => date("d M Y", $ssl['validFrom_time_t']),
            'validTo' => date("d M Y", $ssl['validTo_time_t']),
            'san' => implode(', ', $san),
        ];
    }

    private function _getStatusIcon($status)
    {
        $url = pm_Context::getBaseUrl() . "/images/ssl-{$status}.png";
        $title = $this->lmsg('list.domains.status' . ucfirst($status));
        return '<img src="' . $this->_view->escape($url) . '"'
            . ' alt="' . $this->_view->escape($status) . '"'
            . ' title="' . $this->_view->escape($title) . '"/>';
    }

    private function _getColumns()
    {
        return [
            self::COLUMN_SELECTION,
            'domainName' => [
                'title' => $this->lmsg('list.domains.domainNameColumn'),
                'searchable' => true,
            ],
            'status' => [
                'title' => '',
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
    }

    private function _getTools()
    {
        $tools = [];
        if ($this->_isLetsEncryptInstalled) {
            $letsEncryptUrl = pm_Context::getActionUrl('index', 'letsencrypt');
            $tools[] = [
                'title' => $this->lmsg('list.domains.letsencryptDomains'),
                'description' => $this->lmsg('list.domains.letsencryptDomainsDescription'),
                'execGroupOperation' => $letsEncryptUrl,
            ];
        } else {
            $installUrl = pm_Context::getActionUrl('index', 'install-letsencrypt');
            $tools[] = [
                'title' => $this->lmsg('list.domains.installLetsencrypt'),
                'description' => $this->lmsg('list.domains.installLetsencryptDescription'),
                'link' => "javascript:Jsw.redirectPost('{$installUrl}')",
            ];
        }
        return $tools;
    }
}
