<?php

class Modules_SecurityWizard_Extension
{
    public static function isInstalled($name)
    {
        return file_exists(dirname(pm_Context::getPlibDir()) . "/" . $name);
    }

    public static function install($url)
    {
        $request = "<server><install-module><url>{$url}</url></install-module></server>";
        $response = pm_ApiRpc::getService('1.6.7.0')->call($request);
        $result = $response->server->{'install-module'}->result;
        if ($result->status != "ok") {
            throw new pm_Exception("Installation failed: {$result->errtext}");
        }
    }
}
