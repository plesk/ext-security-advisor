<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH.

namespace PleskExt\SecurityAdvisor;

class Config
{
    private static $_instance = null;

    private $_param = [
        'promoteSymantec' => false, // TODO: change by true when Symantec SSL will work
    ];

    protected function __construct()
    {
        if (!class_exists(\pm_Config::class)) {
            return;
        }

        foreach ($this->_param as $key => $value) {
            $this->_param[$key] = \pm_Config::get($key, $value);
        }
    }

    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function __get($key)
    {
        if (isset($this->_param[$key])) {
            return $this->_param[$key];
        }

        return false;
    }
}
