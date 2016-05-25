<?php

class IndexController extends pm_Controller_Action
{
    public function init()
    {
        parent::init();

        $this->view->pageTitle = $this->lmsg('pageTitle');
        $this->view->tabs = [
            [
                'title' => $this->lmsg('tabs.domains'),
                'action' => 'domain-list',
            ],
            [
                'title' => $this->lmsg('tabs.wordpress'),
                'action' => 'wordpress-list',
            ],
            [
                'title' => $this->lmsg('tabs.settings'),
                'action' => 'settings',
            ],
        ];
    }

    public function indexAction()
    {
        $this->_forward('domain-list');
    }

    public function domainListAction()
    {
    }

    public function wordpressListAction()
    {
    }

    public function settingsAction()
    {
    }
}
