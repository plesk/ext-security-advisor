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
        $this->addElement('checkbox', 'securePanel', [
            'label' => $this->lmsg('form.settings.securePaneltitle'),
            'description' => $this->lmsg('form.settings.securePaneldesc'),
            'value' => Modules_SecurityWizard_Helper_PanelCertificate::isPanelSecured($this->_getHostname()),
        ]);
        $this->addElement('text', 'securePanelHostname', [
            'label' => $this->lmsg('form.settings.securePanelHostnametitle'),
            'value' => $this->_getHostname(),
            'class' => 'f-large-size',
            'required' => true,
            'validators' => [new Zend_Validate_Hostname()],
        ]);

        $this->addElement('checkbox', 'http2', [
            'label' => $this->lmsg('form.settings.http2title'),
            'description' => $this->lmsg('form.settings.http2desc'),
            'value' => false, // TODO: set correct value
        ]);

        $this->addControlButtons([
            'cancelLink' => $this->_returnUrl,
        ]);
    }

    public function isValid($data)
    {
        if (!$data['securePanel']) {
            $this->securePanelHostname->setRequired(false);
            $this->securePanelHostname->clearValidators();
        }
        return parent::isValid($data);
    }

    public function process()
    {
        if ($this->securePanel->getValue()) {
            $hostname = $this->securePanelHostname->getValue();
            if ($this->_isDomainRegisteredInPlesk($hostname)) {
                Modules_SecurityWizard_Letsencrypt::run($hostname, true);
            } else {
                $res = pm_ApiCli::callSbin('letsencrypt-hostname.sh', [$hostname]);
                if ($res['code']) {
                    throw new pm_Exception($res['stdout'] . $res['stderr']);
                }
            }
            pm_Settings::set('secure-panel-hostname', $hostname);
        }
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
        return Modules_SecurityWizard_Helper_Hostname::getServerHostname();
    }

    private function _isDomainRegisteredInPlesk($domain)
    {
        $request = <<<APICALL
        <site>
            <get>
                <filter>
                    <name>{$domain}</name>
                </filter>
                <dataset>
                    <gen_info/>
                </dataset>
            </get>
        </site>
APICALL;
        $response = pm_ApiRpc::getService()->call($request);
        if ($response->site->get->result->status == 'error' && '1013' == $response->site->get->result->errcode) {
            return false;
        }
        return true;
    }
}
