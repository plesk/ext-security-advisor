<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
class Modules_SecurityAdvisor_Letsencrypt
{
    const INSTALL_URL = 'https://ext.plesk.com/packages/f6847e61-33a7-4104-8dc9-d26a0183a8dd-letsencrypt/download';
    const NAME = 'letsencrypt';

    public static function isInstalled()
    {
        return Modules_SecurityAdvisor_Extension::isInstalled(static::NAME);
    }

    public static function install()
    {
        Modules_SecurityAdvisor_Extension::install(static::INSTALL_URL);
    }

    public static function isCertificate($certificateName)
    {
        return false !== stripos($certificateName, 'Lets Encrypt');
    }

    public static function runDomain(pm_Domain $domain)
    {
        $domainNames = [$domain->getName()];

        $db = pm_Bootstrap::getDbAdapter();

        $select = $db->select()->from('dom_param')
            ->where('param = "seoRedirect"')
            ->where('dom_id = ?', $domain->getId());
        $row = $db->fetchRow($select);
        if ($row && 'www' == $row['val']) {
            $domainNames[] = "www.{$domain->getName()}";
        }

        static::run($domainNames);
    }

    public static function run($domainNames, $securePanel = false)
    {
        $options = [];
        foreach ((array)$domainNames as $domainName) {
            $options[] = '-d';
            $options[] = $domainName;
        }
        if ($securePanel) {
            $options[] = '--letsencrypt-plesk:plesk-secure-panel';
        }
        $email = pm_Client::getByLogin('admin')->getProperty('email');
        if ($email) {
            $options[] = '--email';
            $options[] = $email;
        } else {
            $options[] = '--register-unsafely-without-email';
        }
        $options[] = '--non-interactive';

        $result = pm_ApiCli::callSbin('letsencrypt.sh', $options, pm_ApiCli::RESULT_FULL);
        if ($result['code']) {
            throw new pm_Exception("{$result['stdout']}\n{$result['stderr']}");
        }
    }

    public static function countInsecureDomains()
    {
        return pm_Bootstrap::getDbAdapter()->fetchOne("SELECT COUNT(*) FROM hosting WHERE certificate_id = 0");
    }
}
