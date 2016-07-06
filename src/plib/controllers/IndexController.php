<?php

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

        $this->_redirect('index/domain-list');
    }

    public function installLetsencryptAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Post request is required');
        }
        Modules_SecurityAdvisor_Extension::install(Modules_SecurityAdvisor_Letsencrypt::INSTALL_URL);
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
                // TODO: check access
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
        // handle post request
        if ($this->getRequest()->isPost()) {
            // enable http2
            if (isset($_POST['btn_http2_enable'])) {
                list($code, $msgs) = $this->_enable_http2('enable');
                if ($code != 0) {
                    foreach ($msgs as $msg) {
                        $this->_status->addMessage('error', $msg);
                    }
                }
                // disable http2
            } elseif (isset($_POST['btn_http2_disable'])) {
                list($code, $msgs) = $this->_enable_http2('disable');
                if ($code != 0) {
                    foreach ($msgs as $msg) {
                        $this->_status->addMessage('error', $msg);
                    }
                }
                // install datagrid scanner
            } elseif (isset($_POST['btn_datagrid_install'])) {
                $dg = new Modules_SecurityAdvisor_Datagrid();
                $dg->install();
                // install patchman
            } elseif (isset($_POST['btn_patchman_install'])) {
                $pm = new Modules_SecurityAdvisor_Patchman();
                $pm->install();
            }
            $this->_redirect('index/system');
        }
        $base_url = pm_Context::getBaseUrl();

        // set secure panel state
        if (Modules_SecurityAdvisor_Helper_PanelCertificate::isPanelSecured()) {
            $secure_panel_state   = '<img src="' . $base_url . '/images/icon-ready.png" width="30px" height="30px" /><div class="secw-state-ready">' . $this->lmsg('controllers.system.stateEnabled') . '</div>';
            $secure_panel_content = $this->lmsg('controllers.system.panelSecured');
            $secure_panel_class   = 'secw-settings-enabled';
        } else {
            $secure_panel_state   = '<img src="' . $base_url . '/images/icon-not-ready.png" width="30px" height="30px" /><div class="secw-state-not-ready">' . $this->lmsg('controllers.system.stateDisabled') . '</div>';
            $secure_panel_content = '<a href="' . pm_Context::getActionUrl('index', 'secure-panel') . '">' . $this->lmsg('controllers.system.panelNotSecured') . '</a>';
            $secure_panel_class   = 'secw-settings-disabled';
        }

        // set http2 state
        if (Modules_SecurityAdvisor_Helper_Http2::isHttp2Enabled()) {
            $http2_state   = '<img src="' . $base_url . '/images/icon-ready.png" width="30px" height="30px" /><div class="secw-state-ready">' . $this->lmsg('controllers.system.stateEnabled') . '</div>';
            $http2_content = '<span title="' . $this->lmsg('controllers.system.http2Desc') . '">' . $this->lmsg('controllers.system.http2Enabled') . '</span>.';
            $http2_class   = 'secw-settings-enabled';
        } else {
            $http2_state   = '<img src="' . $base_url . '/images/icon-not-ready.png" width="30px" height="30px" /><div class="secw-state-not-ready">' . $this->lmsg('controllers.system.stateDisabled') . '</div>';
            $http2_content = '<input type="submit" title="' . $this->lmsg('controllers.system.http2Desc') . '" name="btn_http2_enable" value="' . $this->lmsg('controllers.system.http2Button') . '" class="secw-link-button" onclick="show_busy(\'secw-http2-state\');" />';
            $http2_class   = 'secw-settings-disabled';
        }

        // set datagrid state
        $dg = new Modules_SecurityAdvisor_Datagrid();
        if ($dg->isInstalled()) {
            if ($dg->isActive()) {
                $datagrid_state   = '<img src="' . $base_url . '/images/icon-ready.png" width="30px" height="30px" /><div class="secw-state-ready">' . $this->lmsg('controllers.system.stateRunning') . '</div>';
                $datagrid_content = '<a href="/modules/dgri" title="' . $this->lmsg('controllers.system.datagridDesc') . '">' . $this->lmsg('controllers.system.datagrid') . '</a>';
                $datagrid_class   = 'secw-settings-enabled';

                /*
                // get eval results from datagrid
                $res = $dg->run('extended');
                // dbg:  $this->_status->addMessage('info', $res);
                try {
                    $evj = json_encode($res);
                    $ev = json_decode($evj, true);
                } catch (Exception $e) {
                    // ignore
                }
                */
            } else {
                $datagrid_state   = '<img src="' . $base_url . '/images/icon-partial.png" width="30px" height="30px" /><div class="secw-state-partial">' . $this->lmsg('controllers.system.stateNotActivated') . '</div>';
                $datagrid_content = '<a href="/modules/dgri" title="' . $this->lmsg('controllers.system.datagridDesc') . '">' . $this->lmsg('controllers.system.datagridActivate') . '</a>';
                $datagrid_class   = 'secw-settings-enabled';
            }
        } else {
            $datagrid_state   = '<img src="' . $base_url . '/images/icon-not-ready.png" width="30px" height="30px" /><div class="secw-state-not-ready">' . $this->lmsg('controllers.system.stateNotInstalled') . '</div>';
            $datagrid_content = '<input type="submit" title="' . $this->lmsg('controllers.system.datagridDesc') . '" name="btn_datagrid_install" value="' . $this->lmsg('controllers.system.datagridInstall') . '" class="secw-link-button" onclick="show_busy(\'secw-datagrid-state\');" />';
            $datagrid_class   = 'secw-settings-disabled';
        }

        // set patchman state
        $pm = new Modules_SecurityAdvisor_Patchman();
        if ($pm->isInstalled()) {
            if ($pm->isActive()) {
                $patchman_state   = '<img src="' . $base_url . '/images/icon-ready.png" width="30px" height="30px" /><div class="secw-state-ready">' . $this->lmsg('controllers.system.stateRunning') . '</div>';
                $patchman_content = '<a href="/modules/patchmaninstaller" title="' . $this->lmsg('controllers.system.patchmanDesc') . '">' . $this->lmsg('controllers.system.patchman') . '</a>';
                $patchman_class   = 'secw-settings-enabled';
            } else {
                $patchman_state   = '<img src="' . $base_url . '/images/icon-partial.png" width="30px" height="30px" /><div class="secw-state-partial">' . $this->lmsg('controllers.system.stateNotActivated') . '</div>';
                $patchman_content = '<a href="/modules/patchmaninstaller" title="' . $this->lmsg('controllers.system.patchmanDesc') . '">' . $this->lmsg('controllers.system.patchmanActivate') . '</a>';
                $patchman_class   = 'secw-settings-enabled';
            }
        } else {
            $patchman_state   = '<img src="' . $base_url . '/images/icon-not-ready.png" width="30px" height="30px" /><div class="secw-state-not-ready">' . $this->lmsg('controllers.system.stateNotInstalled') . '</div>';
            $patchman_content = '<input type="submit" title="' . $this->lmsg('controllers.system.patchmanDesc') . '" name="btn_patchman_install" value="' . $this->lmsg('controllers.system.patchmanInstall') . '" class="secw-link-button" onclick="show_busy(\'secw-patchman-state\');" />';
            $patchman_class   = 'secw-settings-disabled';
        }
        // set view contents:  form
        $file = pm_Context::getHtdocsDir() . '/templates/settings.php';
        $tp = new Modules_SecurityAdvisor_Template($file);
        $tp->set('base_url', pm_Context::getBaseUrl());

        $tp->set('secure_panel_state', $secure_panel_state);
        $tp->set('secure_panel_content', $secure_panel_content);
        $tp->set('secure_panel_class', $secure_panel_class);

        $tp->set('http2_state', $http2_state);
        $tp->set('http2_content', $http2_content);
        $tp->set('http2_class', $http2_class);

        $tp->set('datagrid_state', $datagrid_state);
        $tp->set('datagrid_content', $datagrid_content);
        $tp->set('datagrid_class', $datagrid_class);

        $tp->set('patchman_state', $patchman_state);
        $tp->set('patchman_content', $patchman_content);
        $tp->set('patchman_class', $patchman_class);
        $this->view->form = $tp->get_content();
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

    private function _enable_http2($action)
    {
        $msgs = [];

        // determine plesk bin directory
        if ( ($bin_dir = $this->_get_psa_bin()) == '') {
            $msgs[] = 'Failed to determine the Plesk bin directory';
            return array(1, $msgs);
        }

        // verify http2_pref utility is installed
        // bp:  this also verifies the Plesk version is min 12.5.30 build 28 as
        // well as the presense of nginx min 1.9.14.
        if (! file_exists($bin_dir . '/http2_pref')) {
            $msgs[] = 'The http2_pref utility is not installed';
            return array(1, $msgs);
        }

        // exec the http2_pref utility to set the preference for http2
        list($code, $msg) = $this->_set_http2_pref($bin_dir . '/http2_pref',
            $action);
        if ($code != 0) {
            $msgs[] = $msg;
            return array(1, $msgs);
        }

        //@@ FIXME can be enable or disable
        //if (! $this->_http2_enabled()) {
        //    $msgs[] = 'Failed to enable HTTP2 using http2_pref';
        //    return array(1, $msgs);
        //}

        // return success
        return array(0, $msgs);
    }


    //  set the http2 preference
    private function _set_http2_pref($util, $action)
    {
        $msg = '';
        $ret  = null;
        try {
            $ret = pm_ApiCli::callSbin('set_http2_pref.sh', array($util, $action));
            $msg = $ret['stdout'] . $ret['stderr'];
        }
        catch (pm_Exception $e) {
            $msg = $e->getMessage();
            $ret  = array('code' => 2);
        }
        return array($ret['code'], $msg);
    }


    // return the directory containing the http2_pref utility
    private function _get_psa_bin()
    {
        // get Plesk version string
        $ver_file = '/usr/local/psa/version';
        $vstr = file_get_contents($ver_file);
        if ($vstr === false) {
            return('');
        }

        // split into array
        $vers = explode(' ', $vstr);
        if (sizeof($vers) < 2) {
            return('');
            }

        // return psa bin directory by OS
        switch ($vers[1]) {
            case 'Debian':
            case 'Ubuntu':
                return '/opt/psa/bin';
                break;
            case 'CentOS':
            case 'RedHat':
            case 'CloudLinux':
                return '/usr/local/psa/bin';
                break;
        }

        // return empty string on failure
        return('');
    }


    // return true if extension is installed else false
    private function _extension_installed()
    {
        return true;
    }

}
