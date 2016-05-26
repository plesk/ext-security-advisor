<?php

class Modules_SecurityWizard_Promo_Home extends pm_Promo_AdminHome
{
    private $_step;

    public function getTitle()
    {
        return $this->lmsg('promo.title');
    }

    public function getText()
    {
        return $this->lmsg('promo.text' . $this->_getStep());
    }

    public function getButtonText()
    {
        return $this->lmsg('promo.button');
    }

    public function getButtonUrl()
    {
        switch ($this->_getStep()) {
            case 1 :
                return pm_Context::getActionUrl('index', 'domain-list');
            case 2 :
                return pm_Context::getActionUrl('index', 'wordpress-list');
            case 3 :
                return pm_Context::getActionUrl('index', 'settings');
            default :
                return pm_Context::getBaseUrl();
        }
    }

    public function getIconUrl()
    {
        return pm_Context::getBaseUrl() . '/images/home-promo.png';
    }

    private function _getStep()
    {
        if (is_null($this->_step)) {
            if (Modules_SecurityWizard_Letsencrypt::countInsecureDomains() > 0) {
                $this->_step = 1;
            } elseif (Modules_SecurityWizard_Helper_WordPress::getNotSecureCount() > 0) {
                $this->_step = 2;
            } else if (!$this->_isPanelSecured()) {
                $this->_step = 3;
            }
        }
        return $this->_step;
    }
    
    private function _isPanelSecured()
    {
        $hostname = pm_Settings::get('secure-panel-hostname');
        if (empty($hostname)) {
            return false;
        }
        return Modules_SecurityWizard_Helper_PanelCertificate::isPanelSecured($hostname);
    }
}
