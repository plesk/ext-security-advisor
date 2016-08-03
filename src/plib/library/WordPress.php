<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
class Modules_SecurityAdvisor_WordPress
{
    const INSTALL_URL = 'https://ext.plesk.com/packages/00d002a7-3252-4996-8a08-aa1c89cf29f7-wp-toolkit/download';
    const NAME = 'wp-toolkit';

    public static function isInstalled()
    {
        return Modules_SecurityAdvisor_Extension::isInstalled(static::NAME);
    }

    public static function install()
    {
        Modules_SecurityAdvisor_Extension::install(self::INSTALL_URL);
    }

    /**
     * @param array $args
     * @throws pm_Exception
     */
    public static function call($args)
    {
        $res = pm_ApiCli::call('extension', array_merge([
            '--exec-api',
            static::NAME,
        ], $args));

        if (0 !== $res['code']) {
            throw new pm_Exception($res['stdout'] . $res['stderr']);
        }
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
    public static function getDbAdapter()
    {
        $fileManager = new pm_ServerFileManager();
        $dbName = $fileManager->joinPath(PRODUCT_VAR, 'modules', static::NAME, static::NAME . '.sqlite3');
        return new Zend_Db_Adapter_Pdo_Sqlite(['dbname' => $dbName]);
    }
}
