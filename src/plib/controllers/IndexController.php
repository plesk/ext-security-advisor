<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
class IndexController extends pm_Controller_Action
{
    protected $_accessLevel = 'admin';

    public function init()
    {
        parent::init();

        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl() . 'css/styles-secadv.css');

        $this->view->pageTitle = $this->lmsg('pageTitle');

        $this->view->tabs = [
            [
                'title' => $this->lmsg('tabs.domains')
                    . $this->_getBadge(Modules_SecurityAdvisor_Letsencrypt::countInsecureDomains()),
                'action' => 'domain-list',
            ],
            [
                'title' => $this->lmsg('tabs.wordpress')
                    . $this->_getBadge(Modules_SecurityAdvisor_Helper_WordPress::getNotSecureCount()),
                'action' => 'wordpress-list',
            ],
            [
                'title' => $this->lmsg('tabs.system'),
                'action' => 'system',
            ],
        ];
    }

    private function _getBadge($count)
    {
        if ($count > 0) {
            return ' <span class="badge-new">' . $count . '</span>';
        }
        return '';
    }

    public function indexAction()
    {
        $this->_forward('domain-list');
    }

    public function domainListAction()
    {
        $this->view->progress = Modules_SecurityAdvisor_Helper_Async::progress();
        $this->view->list = $this->_getDomainsList();
    }

    public function progressDataAction()
    {
        $this->_helper->json(Modules_SecurityAdvisor_Helper_Async::progress());
    }

    public function closeMessageAction()
    {
        Modules_SecurityAdvisor_Helper_Async::close($this->_getParam('status'), $this->_getParam('id'));
        $this->_helper->json([]);
    }

    public function domainListDataAction()
    {
        $this->_helper->json($this->_getDomainsList()->fetchData());
    }

    private function _getDomainsList()
    {
        $list = new Modules_SecurityAdvisor_View_List_Domains($this->view, $this->_request);
        $list->setDataUrl(['action' => 'domain-list-data']);
        return $list;
    }

    public function letsencryptAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Post request is required');
        }
        $async = new Modules_SecurityAdvisor_Helper_Async((array)$this->_getParam('ids'));
        $async->runLetsencrypt();

        $this->_redirect(pm_Context::getActionUrl('index', 'domain-list'), ['prependBase' => false]);
    }

    public function installLetsencryptAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Post request is required');
        }
        Modules_SecurityAdvisor_Letsencrypt::install();
        $this->_redirect('index/domain-list');
    }

    public function wordpressListAction()
    {
        $this->view->list = $this->_getWordpressList();
    }

    public function wordpressListDataAction()
    {
        $this->_helper->json($this->_getWordpressList()->fetchData());
    }

    private function _getWordpressList()
    {
        $list = new Modules_SecurityAdvisor_View_List_Wordpress($this->view, $this->_request);
        $list->setDataUrl(['action' => 'wordpress-list-data']);
        return $list;
    }

    public function switchWordpressToHttpsAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Post request is required');
        }

        $failures = [];
        foreach ((array)$this->_getParam('ids') as $wpId) {
            try {
                Modules_SecurityAdvisor_Helper_WordPress::switchToHttps($wpId);
            } catch (pm_Exception $e) {
                $failures[] = $e->getMessage();
            }
        }

        if (empty($failures)) {
            $this->_status->addInfo($this->lmsg('controllers.switchWordpressToHttps.successMsg'));
        } else {
            $message = $this->lmsg('controllers.switchWordpressToHttps.errorMsg') . '<br>';
            $message .= implode('<br>', array_map([$this->view, 'escape'], $failures));
            $this->_status->addError($message, true);
        }

        $this->_helper->json([
            'status' => empty($failures) ? 'success' : 'error',
            'redirect' => pm_Context::getActionUrl('index', 'wordpress-list'),
        ]);
    }

    public function systemAction()
    {
        if ($this->getRequest()->isPost()) {
            if ($this->_getParam('btn_http2_enable')) {
                Modules_SecurityAdvisor_Helper_Http2::enable();
            } elseif ($this->_getParam('btn_http2_disable')) {
                Modules_SecurityAdvisor_Helper_Http2::disable();
            } elseif ($this->_getParam('btn_letsencrypt_install')) {
                Modules_SecurityAdvisor_Letsencrypt::install();
            } elseif ($this->_getParam('btn_datagrid_install')) {
                Modules_SecurityAdvisor_Datagrid::install();
            } elseif ($this->_getParam('btn_patchman_install')) {
                Modules_SecurityAdvisor_Patchman::install();
            }
            $this->_redirect('index/system');
        }

        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl() . 'css/styles-secw.css');

        $this->view->isPanelSecured = Modules_SecurityAdvisor_Helper_PanelCertificate::isPanelSecured();
        $this->view->isLetsencryptInstalled = Modules_SecurityAdvisor_Letsencrypt::isInstalled();
        $this->view->isHttp2Enabled = Modules_SecurityAdvisor_Helper_Http2::isHttp2Enabled();
        $this->view->isDatagridInstalled = Modules_SecurityAdvisor_Datagrid::isInstalled();
        $this->view->isDatagridActive = Modules_SecurityAdvisor_Datagrid::isActive();
        $this->view->isPatchmanInstalled = Modules_SecurityAdvisor_Patchman::isInstalled();
        $this->view->isPatchmanActive = Modules_SecurityAdvisor_Patchman::isActive();
    }

    public function securePanelAction()
    {
        $this->view->pageTitle = $this->lmsg('controllers.securePanel.pageTitle');
        $returnUrl = pm_Context::getActionUrl('index', 'system');
        $form = new Modules_SecurityAdvisor_View_Form_SecurePanel([
            'returnUrl' => $returnUrl
        ]);
        if ($this->_request->isPost() && $form->isValid($this->_request->getPost())) {
            try {
                $form->process();
            } catch (pm_Exception $e) {
                $this->_status->addError($e->getMessage());
                $this->_helper->json(['redirect' => $returnUrl]);
            }
            $this->_status->addInfo($this->lmsg('controllers.securePanel.save.successMsg'));
            $this->_helper->json(['redirect' => $returnUrl]);
        }
        $this->view->form = $form;
    }
}
