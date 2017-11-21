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

    /**
     * Return all vendor domains (own + customer`s)
     *
     * @param \pm_Client $client
     * @return array
     */
    public static function getAllVendorDomains(\pm_Client $client)
    {
        if ($client->isAdmin()) {
            $domains = \Db_Table_Broker::get('domains')->fetchAll();
        } else {
            $clientId = $client->getId();
            $domains = \Db_Table_Broker::get('domains')
                ->fetchAll("cl_id=$clientId OR vendor_id=$clientId");
        }

        $domains = array_filter($domains->toArray(), function ($domain) use ($client) {
            return $client->hasAccessToDomain($domain['id']);
        });

        return array_map(function ($domain) {
            return new \pm_Domain($domain['id']);
        }, $domains);
    }

    /**
     * Return all vendor domains ids (own + customer`s)
     *
     * @param \pm_Client $client
     * @return array
     */
    public static function getAllVendorDomainsIds(\pm_Client $client)
    {
        $pmDomains = static::getAllVendorDomains($client);

        return array_map(function ($pmDomain) {
            return $pmDomain->getId();
        }, $pmDomains);
    }

    /**
     * Return count of domains without certificate or with invalid certificate
     *
     * @param int $webspaceId
     * @return int
     */
    public static function countInsecure($webspaceId = 0)
    {
        $count = 0;

        $client = \pm_Session::getClient();
        $clientId = $client->getId();

        $certDbTable = \Db_Table_Broker::get('Certificates');
        $select = $certDbTable->select()
            ->setIntegrityCheck(false)
            ->from(
                [
                    'h' => 'hosting',
                ]
            )
            ->join(
                [
                    'd' => 'domains'
                ],
                'd.id = h.dom_id'
            )
            ->joinLeft(
                [
                    'c' => 'certificates'
                ],
                'c.id = h.certificate_id'
            );

        if (!$client->isAdmin() && !$webspaceId) {
            $select = $select
                ->where('d.cl_id = ? OR d.vendor_id = ?', $clientId, $clientId);
        } elseif ($webspaceId) {
            $select = $select
                ->where('d.id = ? OR d.webspace_id = ?', $webspaceId, $webspaceId);
        }

        $items = $certDbTable->fetchAll($select);

        foreach ($items as $item) {
            $domain = new \pm_Domain($item->dom_id);
            if (!self::isOperable($domain)) {
                continue;
            }

            if (!intval($item->certificate_id)
                || !($cert = urldecode($item->cert))
                || !($ssl = openssl_x509_parse($cert))
            ) {
                $count++;
                continue;
            }

            $certData = ($item->ca_cert ? urldecode($item->ca_cert) . "\n" : "") . $cert;
            if (!\Modules_SecurityAdvisor_Helper_Ssl::verifyCertificate($certData)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Check if we can operate with domain (domain may be not supported, not active, ...).
     *
     * @param \pm_Domain $domain
     * @return bool
     */
    public static function isOperable(\pm_Domain $domain)
    {
        if (!\pm_Session::getClient()->hasAccessToDomain($domain->getId())
            || 0 === strpos($domain->getProperty('displayName'), '*')
            || $domain->getProperty('status') != STATUS_ACTIVE
            || $domain->getProperty('htype') != 'vrt_hst'
        ) {
            return false;
        }

        return true;
    }
}
