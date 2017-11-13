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
        $email = pm_Session::getClient()->getProperty('email');
        if ($email) {
            $options[] = '--email';
            $options[] = $email;
        } else {
            $options[] = '--register-unsafely-without-email';
        }
        $options[] = '--non-interactive';

        $result = version_compare(\pm_ProductInfo::getVersion(), '17.0') >=0
            ? \pm_ApiCli::call('extension', array_merge(['--exec', 'letsencrypt', 'cli.php'], $options), \pm_ApiCli::RESULT_FULL)
            : \pm_ApiCli::callSbin('letsencrypt.sh', $options, \pm_ApiCli::RESULT_FULL);

        if ($result['code']) {
            throw new pm_Exception("{$result['stdout']}\n{$result['stderr']}");
        }
    }

    public static function isSecurePanelSupport()
    {
        return Modules_SecurityAdvisor_Extension::isVersion(static::NAME, '>', '2.1.0');
    }

    public static function getSecurePanelFormUrl()
    {
        $currentModuleId = pm_Context::getModuleId();
        pm_Context::init(static::NAME);
        $securePanelFormUrl = pm_Context::getActionUrl('index', 'secure-panel');
        pm_Context::init($currentModuleId);

        return $securePanelFormUrl;
    }

    public static function isInstallable()
    {
        return \pm_ProductInfo::isUnix() || version_compare(\Modules_SecurityAdvisor_Helper_Utils::getOsVersion(), '6.2') >= 0;
    }
}
