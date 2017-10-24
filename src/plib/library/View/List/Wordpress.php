<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

require_once __DIR__ . '/../../../vendor/autoload.php';

class Modules_SecurityAdvisor_View_List_Wordpress extends pm_View_List_Simple
{
    /**
     * @var Modules_SecurityAdvisor_Helper_WordPress_Abstract
     */
    private $_wpHelper;

    protected $_subscriptionId = null;

    private $_detailsUrl;

    public function __construct(Zend_View $view, Zend_Controller_Request_Abstract $request, array $options = [])
    {
        if (isset($options['subscriptionId'])) {
            $this->_subscriptionId = $options['subscriptionId'];
            unset($options['subscriptionId']);
        }
        parent::__construct($view, $request, $options);
    }

    protected function _init()
    {
        parent::_init();

        $this->_detailsUrl = version_compare(pm_ProductInfo::getVersion(), '12.5') > 0
            ? '/modules/wp-toolkit/index.php/index/detail/id/%s'
            : '/admin/wordpress/detail/id/%s';

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

            if ($wp['domainId'] > 0 && (is_null($this->_subscriptionId) || $this->_subscriptionId == $wp['domainId'])) {
                $record = [
                    'id' => $wp['id'],
                    'name' => '<a href="' . $this->_getDetailsUrl($wp['id']) . '">' . $this->_view->escape($properties['name']) . '</a>',
                    'url' => '<a href="' . $this->_view->escape($properties['url']) . '" target="_blank">'
                        . $this->_view->escape($properties['url'])
                        . '</a>',
                    'onHttps' => '<img src="' . $this->_view->escape(pm_Context::getBaseUrl() . '/images/' . $httpsImage) . '"'
                        . ' alt="' . $this->_view->escape($httpsImageAlt) . '"'
                        . ' title="' . $this->_view->escape($httpsImageTitle) . '">'
                        . ' ' . $this->_view->escape($httpsImageTitle),
                ];
                if (!$properties['isAlive']) {
                    $domain = \pm_Domain::getByDomainId($wp['domainId']);
                    $record['name'] = ' <span class="tooltipData">' . $properties['error'] . '</span>'
                        . '<img src="' . \pm_Context::getBaseUrl() . 'images/att.png" border="0" /> '
                        . $this->lmsg(
                            'list.wordpress.brokenName',
                            [
                                'domain' => '<a href="' . PleskExt\SecurityAdvisor\Helper\Domain::getDomainOverviewUrl($domain) . '">'
                                    . $this->_view->jsEscape($domain->getDisplayName())
                                    . '</a>',
                                'instance' => '<a href="' . $this->_getDetailsUrl($wp['id']) . '">'
                                    . $this->_view->jsEscape($properties['name'])
                                    . '</a>',
                            ]
                        );
                }
                $wordpress[] = $record;
            }
        }

        return $wordpress;
    }

    private function _getDetailsUrl($id)
    {
        return sprintf($this->_detailsUrl, $id);
    }

    private function _getColumns()
    {
        return [
            pm_View_List_Simple::COLUMN_SELECTION,
            'name' => [
                'title' => $this->lmsg('list.wordpress.nameColumn'),
                'noEscape' => true,
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
