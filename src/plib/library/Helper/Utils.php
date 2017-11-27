<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

class Modules_SecurityAdvisor_Helper_Utils
{
    public static function getOsVersion()
    {
        return method_exists('pm_ProductInfo', 'getOsVersion')
            ? \pm_ProductInfo::getOsVersion()
            : php_uname('r');
    }

    /**
     * Convert idn url to utf8.
     *
     * @param $url
     * @return string
     */
    public static function idnToUtf8($url)
    {
        if (false === strpos($url, 'xn--')) {
            return $url;
        }

        foreach (['https://', 'http://'] as $prefix) {
            if (0 === strpos($url, $prefix)) {
                return $prefix . idn_to_utf8(substr($url, strlen($prefix)));
            }
        }

        return idn_to_utf8($url);
    }

    /**
     * Fetch and returns hostname variants from http request params.
     *
     * @return string|bool
     */
    protected static function _getHostnameHttpRequest()
    {
        /** @var \Zend_Controller_Request_Http $request */
        $request = \Zend_Controller_Front::getInstance()->getRequest();
        if (is_null($request)) {
            return false;
        }

        $httpHost = $request->getServer('HTTP_HOST');

        return parse_url($httpHost, PHP_URL_HOST);
    }

    /**
     * Fetch and returns hostname variants from Panel API_RPC.
     *
     * @return string|bool
     */
    protected static function _getHostnamePanelApi()
    {
        $response = \pm_ApiRpc::getService()->call('<server><get><gen_info/></get></server>');
        if (empty($response->server->get->result->status)
            || $response->server->get->result->status != 'ok'
            || empty($response->server->get->result->gen_info->server_name)
        ) {
            return false;
        }

        return $response->server->get->result->gen_info->server_name;
    }

    /**
     * Fetch and returns hostname from Panel CLI utility.
     *
     * @return string|bool
     */
    protected static function _getHostnamePanelCli()
    {
        if (\pm_ProductInfo::isWindows()) {
            if (version_compare(\pm_ProductInfo::getVersion(), '17.0') < 0) {
                return false;
            }
            $cmd = 'ifmng';
            $args = ['--get-hostname'];
        } else {
            $cmd = 'serverconf';
            $args = ['--key', 'FULLHOSTNAME'];
        }

        $response = \pm_ApiCli::callSbin($cmd, $args);

        if ($response['code']) {
            return false;
        }

        return trim($response['stdout']);
    }

    /**
     * Fetch and returns hostname
     *
     * @return string
     * @throws \pm_Exception
     */
    public static function getHostname()
    {
        $result = static::_getHostnamePanelApi() ?: static::_getHostnameHttpRequest() ?: static::_getHostnamePanelCli();
        if (!$result) {
            throw new \pm_Exception('Failed to fetch hostname.');
        }

        return $result;
    }

    /**
     * Returns panel's port
     *
     * @return int
     */
    public static function getPanelPort()
    {
        return static::_getPortHttpRequest() ?: 8443;
    }

    protected static function _getPortHttpRequest()
    {
        /** @var \Zend_Controller_Request_Http $request */
        $request = \Zend_Controller_Front::getInstance()->getRequest();
        if (is_null($request)) {
            return false;
        }

        $httpHost = $request->getServer('HTTP_HOST');

        return (int)parse_url($httpHost, PHP_URL_PORT);
    }
}
