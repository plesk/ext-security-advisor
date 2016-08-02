<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
abstract class Modules_SecurityAdvisor_Helper_WordPress_Abstract
{
    protected $_dbAdapter;

    public function __construct()
    {
        $this->_dbAdapter = $this->_getDbAdapter();
    }

    /**
     * @return bool
     */
    public function isAvailable()
    {
        return $this->isAllowedByLicense() && $this->isInstalled();
    }

    /**
     * @return bool
     */
    public function isAllowedByLicense()
    {
        return (bool)(new pm_License())->getProperty('wordpress-toolkit');
    }

    /**
     * @return bool
     */
    public function isInstalled()
    {
        return true;
    }

    /**
     * @param int $wpId
     * @throws pm_Exception
     */
    public function switchToHttps($wpId)
    {
        if (!$this->isAvailable()) {
            throw new pm_Exception('The WordPress Toolkit is not available.');
        }

        $wordpress = $this->_getInstance($wpId);
        if (false === $wordpress) {
            throw new pm_Exception("Instance with id = {$wpId} not found");
        }
        $properties = $this->getInstanceProperties($wpId);
        if (0 === strpos($properties['url'], 'https://')) {
            // force the replacement anyway
            $properties['url'] = str_replace('https://', 'http://', $properties['url']);
        }

        try {
            $this->_callWpCli($wordpress, [
                'search-replace',
                $properties['url'],
                str_replace('http://', 'https://', $properties['url']),
                '--skip-columns=guid',
            ]);
        } catch (pm_Exception $e) {
            throw new pm_Exception('Cannot switch from HTTP to HTTPS: ' . $e->getMessage());
        }

        try {
            $this->_resetCache($wpId);
        } catch (pm_Exception $e) {
            throw new pm_Exception('Cannot clear WordPress cache: ' . $e->getMessage());
        }
    }

    /**
     * @param int $wpId
     * @return array
     */
    public function getInstanceProperties($wpId)
    {
        if (!$this->isAvailable()) {
            return [];
        }

        $allProperties = $this->_getInstanceProperties($wpId);
        $properties = [];
        foreach ($allProperties as $p) {
            $properties[$p['name']] = $p['value'];
        }
        return $properties;
    }

    /**
     * @return int
     */
    public function getNotSecureCount()
    {
        if (!$this->isAvailable()) {
            return 0;
        }
        return $this->_getNotSecureCount();
    }

    /**
     * @return array
     */
    public function getInstances()
    {
        if (!$this->isAvailable()) {
            return [];
        }
        return $this->_getInstances();
    }

    /**
     * @return array
     */
    abstract protected function _getInstances();
    /**
     * @param int $wpId
     * @return array
     */
    abstract protected function _getInstance($wpId);
    /**
     * @param int $wpId
     * @return array
     */
    abstract protected function _getInstanceProperties($wpId);
    /**
     * @return int
     */
    abstract protected function _getNotSecureCount();
    /**
     * @param array $wordpress
     * @param string $args
     * @throws pm_Exception
     */
    abstract protected function _callWpCli($wordpress, $args);
    /**
     * @param int $wpId
     * @throws pm_Exception
     */
    abstract protected function _resetCache($wpId);
    /**
     * @return Zend_Db_Adapter_Abstract
     */
    abstract protected function _getDbAdapter();

}
