<?php

class Modules_SecurityWizard_Datagrid
{
    const INSTALL_URL = 'https://ext.plesk.com/packages/e757450e-40a5-44e5-a35d-8c4c50671019-dgri/download';
    const NAME = 'dgri';
    const CONF_PATH = '/etc/dgri-report.conf';

    public static function isInstalled()
    {
    return Modules_SecurityWizard_Extension::isInstalled(self::NAME);
    }

    public static function isActive()
    {
    return file_exists(self::CONF_PATH);
    }

    public static function install()
    {
    return Modules_SecurityWizard_Extension::install(self::INSTALL_URL);
    }

    public static function run($option)
    {
        $options = [$option];
        $result = pm_ApiCli::callSbin('dgri.sh', $options);
        if ($result['code']) {
            throw new pm_Exception("{$result['stdout']}\n{$result['stderr']}");
            return [];
        }
        return $result['stdout'];
    }
}
