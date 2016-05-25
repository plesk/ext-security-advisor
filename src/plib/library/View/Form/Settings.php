<?php

class Modules_SecurityWizard_View_Form_Settings extends pm_Form_Simple
{
    private $_returnUrl;

    public function __construct($options)
    {
        $this->_returnUrl = $options['returnUrl'];
        parent::__construct();
    }

    public function init()
    {
        $this->addElement('checkbox', 'http2', [
            'label' => $this->lmsg('form.settings.http2title'),
            'description' => $this->lmsg('form.settings.http2desc'),
            'value' => false, // TODO: set correct value
        ]);

        $this->addControlButtons([
            'cancelLink' => $this->_returnUrl,
        ]);
    }

    public function process()
    {
        // TODO: process settings
    }

}
