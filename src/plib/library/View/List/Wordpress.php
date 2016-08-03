<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
class Modules_SecurityAdvisor_View_List_Wordpress extends pm_View_List_Simple
{
    /**
     * @var Modules_SecurityAdvisor_Helper_WordPress_Abstract
     */
    private $_wpHelper;

    protected function _init()
    {
        parent::_init();

        $this->_wpHelper = Modules_SecurityAdvisor_Helper_WordPress::get();
        $this->setData($this->_fetchData());
        $this->setColumns($this->_getColumns());
        $this->setTools($this->_getTools());
    }

    private function _fetchData()
    {
        $allWp = $this->_wpHelper->getInstances();
        $wordpress = [];
        foreach ($allWp as $wp) {
            $properties = $this->_wpHelper->getInstanceProperties($wp['id']);
            if (0 === strpos($properties['url'], 'https://')) {
                $httpsImage = 'https-enabled.png';
                $httpsImageAlt = 'enabled';
                $httpsImageTitle = $this->lmsg('list.wordpress.httpsEnableTitle');
            } else {
                $httpsImage = 'https-disabled.png';
                $httpsImageAlt = 'disabled';
                $httpsImageTitle = $this->lmsg('list.wordpress.httpsDisableTitle');
            }

            $wordpress[] = [
                'id' => $wp['id'],
                'name' => $properties['name'],
                'url' => '<a href="' . $this->_view->escape($properties['url']) . '" target="_blank">'
                    . $this->_view->escape($properties['url'])
                    . '</a>',
                'onHttps' => '<img src="' . $this->_view->escape(pm_Context::getBaseUrl() . '/images/' . $httpsImage) . '"'
                    . ' alt="' . $this->_view->escape($httpsImageAlt) . '"'
                    . ' title="' . $this->_view->escape($httpsImageTitle) . '">'
                        . ' ' . $this->_view->escape($httpsImageTitle),
            ];
        }
        return $wordpress;
    }

    private function _getColumns()
    {
        return [
            pm_View_List_Simple::COLUMN_SELECTION,
            'name' => [
                'title' => $this->lmsg('list.wordpress.nameColumn'),
                'noEscape' => false,
                'searchable' => true,
            ],
            'url' => [
                'title' => $this->lmsg('list.wordpress.urlColumn'),
                'noEscape' => true,
            ],
            'onHttps' => [
                'title' => $this->lmsg('list.wordpress.httpsColumn'),
                'noEscape' => true,
            ],
        ];
    }

    private function _getTools()
    {
        $tools = [];
        if ($this->_wpHelper->isAvailable()) {
            $tools[] = [
                'title' => $this->lmsg('list.wordpress.switchToHttpsButtonTitle'),
                'description' => $this->lmsg('list.wordpress.switchToHttpsButtonDesc'),
                'link' => pm_Context::getActionUrl('index', 'switch-wordpress-to-https'),
                'execGroupOperation' => [
                    'url' => pm_Context::getActionUrl('index', 'switch-wordpress-to-https'),
                ],
            ];
        } elseif (!$this->_wpHelper->isInstalled()) {
            $installUrl = pm_Context::getActionUrl('index', 'install-wp-toolkit');
            $tools[] = [
                'title' => $this->lmsg('list.wordpress.installWpToolkit'),
                'description' => $this->lmsg('list.wordpress.installWpToolkitDescription'),
                'link' => "javascript:Jsw.redirectPost('{$installUrl}')",
            ];
        }
        return $tools;
    }
}
