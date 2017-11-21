<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

require_once __DIR__ . '/../vendor/autoload.php';

use PleskExt\SecurityAdvisor\Config;
use PleskExt\SecurityAdvisor\Helper\Domain;

class IndexController extends pm_Controller_Action
{
    protected $_showSymantecPromotion = false;
    protected $_showExtendedFilters = false;

    public function init()
    {
        parent::init();

        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl() . 'css/styles-secadv.css');

        $this->view->pageTitle = $this->lmsg('pageTitle');

        $this->view->tabs = [
            [
                'title' => $this->lmsg('tabs.domains')
                    . $this->_getBadge(Domain::countInsecure()),
                'action' => 'domain-list',
            ],
            [
                'title' => $this->lmsg('tabs.wordpress')
                    . $this->_getBadge(Modules_SecurityAdvisor_Helper_WordPress::get()->getNotSecureCount()),
                'action' => 'wordpress-list',
            ],
        ];

        if (\pm_Session::getClient()->isAdmin()) {
            $this->view->tabs[] = [
                'title' => $this->lmsg('tabs.system'),
                'action' => 'system',
            ];
        }

        $this->_showSymantecPromotion = $this->_isSymantecPromoAvailable();
        $this->view->showSymantecPromotion = $this->_showSymantecPromotion;
        $this->_showExtendedFilters = version_compare(\pm_ProductInfo::getVersion(), '17.0') >= 0;
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
        Modules_SecurityAdvisor_Helper_Subscription::clearContextSubscription();
        $this->_forward('domain-list', null, null, [
            'ignoreContext' => true
        ]);
    }

    public function domainListAction()
    {
        if (($subscriptionId = Modules_SecurityAdvisor_Helper_Subscription::getContextSubscriptionId())
            && !$this->_getParam('ignoreContext', false)
        ) {
            $this->_redirect('/index/subscription/id/' . $subscriptionId);
        }

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
        $list = new Modules_SecurityAdvisor_View_List_Domains($this->view, $this->_request,
            ['showExtendedFilters' => $this->_showExtendedFilters]);
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

        if ($subscriptionId = intval($this->_getParam('subscription'))) {
            $url = pm_Context::getActionUrl('index', 'subscription') . '/id/' . $subscriptionId;
        } else {
            $url = pm_Context::getActionUrl('index', 'domain-list');
        }
        $this->_helper->json([
            'redirect' => $url,
        ]);
    }

    public function installLetsencryptAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Post request is required');
        }
        Modules_SecurityAdvisor_Letsencrypt::install();

        if ($subscriptionId = intval($this->_getParam('subscription'))) {
            $this->_redirect('index/subscription/id/' . $subscriptionId);
        } else {
            $this->_redirect('index/domain-list');
        }
    }

    public function wordpressListAction()
    {
        $wpHelper =  Modules_SecurityAdvisor_Helper_WordPress::get();
        if (!$wpHelper->isAllowedByLicense()) {
           $this->_status->addWarning($this->lmsg('list.wordpress.notAllowed'));
        } elseif (!$wpHelper->isInstalled()) {
            $this->_status->addWarning($this->lmsg('list.wordpress.notInstalled'));
        }

        $subscriptionId = $this->_getParam('subscription');
        $this->view->list = $this->_getWordpressList($subscriptionId ?: null);
        if ($subscriptionId) {
            // check client access to subscription
            if (!\pm_Session::getClient()->hasAccessToDomain($subscriptionId)) {
                throw new \pm_Exception("Access denied to subscription: $subscriptionId");
            }

            $this->view->pageTitle = $this->lmsg('subscription.title', [
                'name' => $this->view->escape((new \pm_Domain($subscriptionId))->getProperty('displayName')),
            ]);
            $this->_setSubscriptionTabs($subscriptionId, 2);
        }
    }

    protected function _setSubscriptionTabs($subscriptionId, $active = 0)
    {
        $this->view->tabs = [
            [
                'title' => $this->lmsg('tabs.domains')
                    . $this->_getBadge(Domain::countInsecure($subscriptionId)),
                'link' => pm_Context::getBaseUrl() . 'index.php/index/subscription/id/' . $subscriptionId,
                'active' => $active == 1,
            ],
            [
                'title' => $this->lmsg('tabs.wordpress')
                    . $this->_getBadge(Modules_SecurityAdvisor_Helper_WordPress::get()->getNotSecureCount($subscriptionId)),
                'link' => pm_Context::getBaseUrl() . 'index.php/index/wordpress-list/subscription/' . $subscriptionId,
                'active' => $active == 2,
            ],
        ];
    }

    public function wordpressListDataAction()
    {
        $this->_helper->json($this->_getWordpressList()->fetchData());
    }

    public function installWpToolkitAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Post request is required');
        }
        Modules_SecurityAdvisor_WordPress::install();
        $this->_redirect('index/wordpress-list');
    }

    private function _getWordpressList($subscriptionId)
    {
        $list = new Modules_SecurityAdvisor_View_List_Wordpress($this->view, $this->_request, ['subscriptionId' => $subscriptionId]);
        $list->setDataUrl(['action' => 'wordpress-list-data']);
        return $list;
    }

    public function switchWordpressToHttpsAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Post request is required');
        }

        $subscriptionId = $this->_getParam('subscription');

        $failures = [];
        foreach ((array)$this->_getParam('ids') as $wpId) {
            try {
                Modules_SecurityAdvisor_Helper_WordPress::get()->switchToHttps($wpId);
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

        $redirect = pm_Context::getActionUrl('index', 'wordpress-list')
            .($subscriptionId ? "/subscription/$subscriptionId" : '');

        $this->_helper->json([
            'status' => empty($failures) ? 'success' : 'error',
            'redirect' => $redirect,
        ]);
    }

    public function systemAction()
    {
        // Only admin has access to system tab
        if (!\pm_Session::getClient()->isAdmin()) {
            throw new \pm_Exception('Access denied');
        }

        $kernelPatchingToolHelper = new Modules_SecurityAdvisor_Helper_KernelPatchingTool();

        if ($this->getRequest()->isPost()) {
            if ($this->_getParam('btn_nginx_enable')) {
                Modules_SecurityAdvisor_Helper_Http2::enableNginx();
            } elseif ($this->_getParam('btn_http2_enable')) {
                Modules_SecurityAdvisor_Helper_Http2::enable();
            } elseif ($this->_getParam('btn_http2_disable')) {
                Modules_SecurityAdvisor_Helper_Http2::disable();
            } elseif ($this->_getParam('btn_letsencrypt_install')) {
                Modules_SecurityAdvisor_Letsencrypt::install();
            } elseif ($this->_getParam('btn_datagrid_install')) {
                Modules_SecurityAdvisor_Datagrid::install();
            } elseif ($this->_getParam('btn_patchman_install')) {
                Modules_SecurityAdvisor_Patchman::install();
            } elseif ($this->_getParam('btn_googleauthenticator_install')) {
                Modules_SecurityAdvisor_GoogleAuthenticator::install();
            } elseif ($this->_getParam('btn_symantec_install') && $this->_showSymantecPromotion) {
                Modules_SecurityAdvisor_Symantec::install();
            }

            // check whether installation of any kernel patching tool requested
            foreach ($kernelPatchingToolHelper->getAvailable() as $tool) {
                $button = 'btn_' . $tool->getName() . '_';
                if ($this->_getParam($button . 'replace')) {
                    foreach ($kernelPatchingToolHelper->getInstalledUnavailable() as $unavailable) {
                        Modules_SecurityAdvisor_Extension::uninstall($unavailable->getName());
                    }
                }
                if ($this->_getParam($button . 'install') || $this->_getParam($button . 'replace')) {
                    try {
                        Modules_SecurityAdvisor_Extension::install($tool->getInstallUrl());
                    } catch (pm_Exception $e) {
                        $this->_status->addError($this->lmsg('controllers.system.kernelPatchingToolInstallError', [
                            'kernelPatchingToolName' => $tool->getName(),
                            'errorMessage' => $e->getMessage()
                        ]));
                    }
                }
            }

            $this->_redirect('index/system');
        }

        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl() . 'css/styles-secw.css');

        $this->view->isPanelSecured = (new Modules_SecurityAdvisor_Helper_PanelCertificate())->isPanelSecured();

        $isLetsencryptInstalled = Modules_SecurityAdvisor_Letsencrypt::isInstalled();
        $this->view->isLetsencryptInstalled = $isLetsencryptInstalled;
        $this->view->securePanelFormUrl = $isLetsencryptInstalled && Modules_SecurityAdvisor_Letsencrypt::isSecurePanelSupport()
            ? Modules_SecurityAdvisor_Letsencrypt::getSecurePanelFormUrl()
            : pm_Context::getActionUrl('index', 'secure-panel');

        $this->view->isNginxInstalled = Modules_SecurityAdvisor_Helper_Http2::isNginxInstalled();
        $this->view->isNginxEnabled = Modules_SecurityAdvisor_Helper_Http2::isNginxEnabled();
        $this->view->isHttp2Enabled = Modules_SecurityAdvisor_Helper_Http2::isHttp2Enabled();

        if (\pm_ProductInfo::isUnix()) {
            $this->view->isDatagridInstalled = Modules_SecurityAdvisor_Datagrid::isInstalled();
            $this->view->isDatagridActive = Modules_SecurityAdvisor_Datagrid::isActive();
            $this->view->isPatchmanInstalled = Modules_SecurityAdvisor_Patchman::isInstalled();
            $this->view->isPatchmanActive = Modules_SecurityAdvisor_Patchman::isActive();
        }

        $this->view->isGoogleAuthenticatorInstalled = Modules_SecurityAdvisor_GoogleAuthenticator::isInstalled();
        $this->view->isGoogleAuthenticatorActive = Modules_SecurityAdvisor_GoogleAuthenticator::isActive();

        if ($this->_showSymantecPromotion) {
            $this->view->isSymantecInstalled = Modules_SecurityAdvisor_Symantec::isInstalled();
            $this->view->isSymantecActive = Modules_SecurityAdvisor_Symantec::isActive();
        }

        if (\pm_ProductInfo::isUnix()) {
            $this->view->kernelRelease = $kernelPatchingToolHelper->getKernelRelease();
            $this->view->isKernelPatchingToolInstalled = $kernelPatchingToolHelper->isAnyInstalled();
            $this->view->isKernelPatchingToolAvailable = $kernelPatchingToolHelper->isAnyAvailable();
            $this->view->installedKernelPatchingTools = $kernelPatchingToolHelper->getInstalled();
            $this->view->installedKernelPatchingToolIsUnavailable = count($kernelPatchingToolHelper->getInstalledUnavailable()) > 0;
            $this->view->installedUnavailable = $kernelPatchingToolHelper->getInstalledUnavailable();
            $this->view->isSeveralKernelPatchingToolAvailable = $kernelPatchingToolHelper->isSeveralAvailable();
            $this->view->firstAvailableKernelPatchingTool = $kernelPatchingToolHelper->getFirstAvailable();
            $this->view->restAvailableKernelPatchingTools = $kernelPatchingToolHelper->getRestAvailable();
        }
    }

    public function securePanelAction()
    {
        if (Modules_SecurityAdvisor_Letsencrypt::isSecurePanelSupport()) {
            $this->_redirect(Modules_SecurityAdvisor_Letsencrypt::getSecurePanelFormUrl(), ['prependBase' => false]);
        }

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

    public function symantecAction()
    {
        $domainId = intval($this->_getParam('domain'));
        if (!$this->_showSymantecPromotion || !Modules_SecurityAdvisor_Symantec::isInstalled() || !$domainId) {
            $this->view->showSymantecPromotion = $this->_showSymantecPromotion;
            return;
        }
        $link = '/modules/symantec/index.php/index/upsell?dom_id=' . $domainId;
        $this->_redirect($link, ['prependBase' => false]);
    }

    public function installSymantecAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Post request is required');
        }
        Modules_SecurityAdvisor_Symantec::install();
        $this->_redirect('index/domain-list');
    }

    public function subscriptionAction()
    {
        if (!($id = $this->_getParam('id'))) {
            if ($contextSubscriptionId = Modules_SecurityAdvisor_Helper_Subscription::getContextSubscriptionId()) {
                $this->_redirect('/index/subscription/id/' . $contextSubscriptionId);
            } else {
                $this->_redirect('/');
            }
        }

        // Check client access to subscription
        if (!\pm_Session::getClient()->hasAccessToDomain($id)) {
            throw new \pm_Exception("Access denied to subscription: $id");
        }

        $this->view->progress = Modules_SecurityAdvisor_Helper_Async::progress();
        $this->view->list = $this->_getSubscription($id);
        $this->view->pageTitle = $this->lmsg('subscription.title', [
            'name' => $this->view->escape((new \pm_Domain($id))->getProperty('displayName')),
        ]);
        $this->_setSubscriptionTabs($id, 1);
        $this->_helper->viewRenderer('domain-list');
    }

    private function _getSubscription($id)
    {
        $list = new Modules_SecurityAdvisor_View_List_Subscription($this->view, $this->_request, [
            'subscriptionId' => $id,
            'showExtendedFilters' => $this->_showExtendedFilters,
        ]);
        $list->setDataUrl(['link' => \pm_Context::getBaseUrl() . 'index.php/index/subscription-data/id/' . $id]);

        return $list;
    }

    public function subscriptionDataAction()
    {
        $this->_helper->json($this->_getSubscription($this->_getParam('id'))->fetchData());
    }

    public function progressLongTaskAction()
    {
        $this->_helper->json(['progress' => \pm_Settings::get('longtask-letsencrypt-progress', 100)]);
    }

    /**
     * Check if symantec promo button availbale.
     *
     * @return bool
     */
    private function _isSymantecPromoAvailable()
    {
        return pm_Session::getClient()->isAdmin()
            && Config::getInstance()->promoteSymantec
            && version_compare(\pm_ProductInfo::getVersion(), '17.0') >= 0;
    }
}
