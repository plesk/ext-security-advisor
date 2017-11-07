<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
class Modules_SecurityAdvisor_Helper_Http2
{
    private static $_nginxStatus;

    private static function _getNginxStatus()
    {
        if (is_null(static::$_nginxStatus)) {
            static::$_nginxStatus = pm_ApiCli::callSbin('nginxmng', ['--status'], pm_ApiCli::RESULT_FULL);
        }
        return static::$_nginxStatus;
    }

    public static function isNginxInstalled()
    {
        return \pm_ProductInfo::isUnix() && static::_getNginxStatus()['code'] == 0;
    }

    public static function isNginxEnabled()
    {
        if (!static::isNginxInstalled()) {
            return false;
        }

        return 0 == strcasecmp('Enabled', trim(static::_getNginxStatus()['stdout']));
    }

    public static function enableNginx()
    {
        pm_ApiCli::callSbin('nginxmng', ['--enable']);
    }

    public static function isHttp2Enabled()
    {
        if (!static::isNginxEnabled()) {
            return false;
        }

        if (version_compare(pm_ProductInfo::getVersion(), '17.0.14') >= 0) {
            return 0 === pm_ApiCli::call('http2_pref', ['--status'], pm_ApiCli::RESULT_CODE);
        } else {
            return (bool)Plesk_Config::get()->webserver->nginxHttp2;
        }
    }

    public static function enable()
    {
        pm_ApiCli::callSbin('set_http2_pref.sh', ['enable']);
    }

    public static function disable()
    {
        pm_ApiCli::callSbin('set_http2_pref.sh', ['disable']);
    }
}
