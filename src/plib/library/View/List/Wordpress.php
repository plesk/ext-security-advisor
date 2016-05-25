<?php

class Modules_SecurityWizard_View_List_Wordpress extends pm_View_List_Simple
{
    protected function _init()
    {
        parent::_init();

        $this->setData($this->_fetchData());
        $this->setColumns($this->_getColumns());
        $this->setTools([]);
    }

    private function _fetchData()
    {
        $db = pm_Bootstrap::getDbAdapter();
        $allWp = $db->query("SELECT * FROM WordpressInstances");
        $wordpress = [];
        foreach ($allWp as $wp) {
            if (pm_Session::getClient()->hasAccessToDomain($wp['subscriptionId'])) {
                //continue;
            }
            $allProperties = $db->query("SELECT * FROM WordpressInstanceProperties WHERE wordpressInstanceId = ?", [$wp['id']]);
            $properties = [];
            foreach ($allProperties as $p) {
                $properties[$p['name']] = $p['value'];
            }
            $wordpress[] = [
                'id' => $wp['id'],
                'name' => $properties['name'],
                'url' => $properties['url'],
            ];
        }
        return $wordpress;
    }

    private function _getColumns()
    {
        return [
            'name' => [
                'title' => $this->lmsg('list.wordpress.nameColumn'),
                'noEscape' => false,
                'searchable' => true,
            ],
            'url' => [
                'title' => $this->lmsg('list.wordpress.urlColumn'),
                'noEscape' => false,
            ],
        ];
    }
}
