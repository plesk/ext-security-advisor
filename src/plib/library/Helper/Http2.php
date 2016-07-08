<?php

class Modules_SecurityAdvisor_Helper_Http2
{
    public static function isHttp2Enabled()
    {
        // TODO private API is used
        return (bool)Plesk_Config::get()->webserver->nginxHttp2;
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
