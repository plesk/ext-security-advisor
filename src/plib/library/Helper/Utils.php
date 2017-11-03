<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

class Modules_SecurityAdvisor_Helper_Utils
{
    public static function countInsecureDomains($webspaceId = 0)
    {
        $client = \pm_Session::getClient();
        $clientId = $client->getId();

        if (!$client->isAdmin() && !$webspaceId) {
            return \pm_Bootstrap::getDbAdapter()->fetchOne(
                'SELECT COUNT(*) FROM hosting h JOIN domains d ON d.id = h.dom_id'
                . ' WHERE (cl_id = ? OR vendor_id = ?) AND h.certificate_id = 0',
                [$clientId, $clientId]
            );
        }

        if ($webspaceId) {
            return \pm_Bootstrap::getDbAdapter()->fetchOne(
                'SELECT COUNT(*) FROM hosting h JOIN domains d ON d.id = h.dom_id'
                . ' WHERE (id = ? OR webspace_id = ?) AND h.certificate_id = 0',
                [$webspaceId, $webspaceId]
            );
        } else {
            return \pm_Bootstrap::getDbAdapter()->fetchOne('SELECT COUNT(*) FROM hosting WHERE certificate_id = 0');
        }
    }
}
