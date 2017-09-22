<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
abstract class Modules_SecurityAdvisor_Extension
{
    public static function isInstalled($name)
    {
        return file_exists(dirname(pm_Context::getPlibDir()) . "/" . $name);
    }

    public static function isVersion($name, $operator, $version)
    {
        try {
            $extensionVersion = self::getVersion($name);
        } catch (\Exception $e) {
            pm_Log::err($e->getMessage());
            return false;
        }

        return version_compare($extensionVersion, $version, $operator);
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

    public static function getVersion($extensionName)
    {
        $metaFile = realpath(pm_Context::getPlibDir() . "../{$extensionName}/meta.xml");
        if (false === $metaFile) {
            throw new pm_Exception("Failed to fetch meta information for extension: {$extensionName}.");
        };

        try {
            $mataData = @file_get_contents($metaFile);
            $extensionInfo = new SimpleXMLElement($mataData);
        } catch (\Exception $e) {
            throw new pm_Exception("Failed to fetch meta information for extension: {$extensionName}. Details:\n{$e->getMessage()}");
        }

        return (string)$extensionInfo->version;
    }

    public static function uninstall($name)
    {
        \pm_ApiCli::call('extension', ['-u', $name]);
    }
}
