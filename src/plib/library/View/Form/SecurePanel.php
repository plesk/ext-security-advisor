<?php

class Modules_SecurityAdvisor_View_Form_SecurePanel extends pm_Form_Simple
{
    private $_returnUrl;

    public function __construct($options)
    {
        $this->_returnUrl = $options['returnUrl'];

        parent::__construct($options);
    }

    public function init()
    {
        $this->addElement('description', 'securePanel', [
            'description' => $this->lmsg('form.settings.securePaneldesc'),
        ]);
        $this->addElement('text', 'securePanelHostname', [
            'label' => $this->lmsg('form.settings.securePanelHostnametitle'),
            'value' => $this->_getHostname(),
            'class' => 'f-large-size',
            'required' => true,
            'validators' => [new Zend_Validate_Hostname()],
        ]);

        $this->addControlButtons([
            'cancelLink' => $this->_returnUrl,
        ]);
    }

    public function process()
    {
        $hostname = $this->securePanelHostname->getValue();
        Modules_SecurityAdvisor_Helper_PanelCertificate::securePanel($hostname);
    }

    private function _getHostname()
    {
        $validator = new Zend_Validate_Hostname();
        $hostname = pm_Settings::get('secure-panel-hostname');
        if (!empty($hostname) && $validator->isValid($hostname)) {
            return $hostname;
        }
        $hostname = parse_url($_SERVER['HTTP_HOST'], PHP_URL_HOST);
        if (!empty($hostname) && $validator->isValid($hostname)) {
            return $hostname;
        }
        $hostname = $_SERVER['HTTP_HOST'];
        if (!empty($hostname) && $validator->isValid($hostname)) {
            return $hostname;
        }
        return Modules_SecurityAdvisor_Helper_Hostname::getServerHostname();
    }
}
