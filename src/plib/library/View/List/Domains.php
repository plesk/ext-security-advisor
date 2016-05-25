<?php

class Modules_SecurityWizard_View_List_Domains extends pm_View_List_Simple
{
    protected function _init()
    {
        parent::_init();

        $this->setData($this->_fetchData());
        $this->setColumns($this->_getColumns());
        $this->setTools([]);
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
                $domains[] = static::_getDomainFromXml($result);
            } catch (pm_Exception $e) {
                continue;
            }
        }
        foreach (pm_ApiRpc::getService()->call($getSites)->site->get->result as $result) {
            try {
                $domains[] = static::_getDomainFromXml($result);
            } catch (pm_Exception $e) {
                continue;
            }
        }
        return $domains;
    }

    private static function _getDomainFromXml($result)
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
        return $domainInfo;
    }

    private function _getColumns()
    {
        return [
            'domainName' => [
                'title' => $this->lmsg('list.domains.domainNameColumn'),
                'noEscape' => false,
                'searchable' => true,
            ],
        ];
    }
}
