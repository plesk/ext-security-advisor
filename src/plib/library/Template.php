<?php

// class for using HTML templates with PHP variable substitution
class Modules_SecurityWizard_Template
{
    protected $_file;
    protected $_keystore = array();

    public function __construct($file = null)
    {
        $this->_file = $file;
    }

    // set key/value pair in keystore
    public function set($key, $val)
    {
        $this->_keystore[$key] = $val;
        return $this;
    }

    // perform variable substitution on and return the template file contents
    public function get_content()
    {
        extract($this->_keystore);
        ob_start();
        include($this->_file);
        return ob_get_clean();
    }
}
