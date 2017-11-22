<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

namespace PleskExt\SecurityAdvisor\Helper;

class Permission
{
    /**
     * @return bool
     */
    public static function hasAccessToSomeDomain()
    {
        $client = \pm_Session::getClient();
        foreach(\pm_Session::getCurrentDomains(true) as $domain) {
            if ($client->hasAccessToDomain($domain->getId())) {
                return true;
            }
        }

        return false;
    }
}
