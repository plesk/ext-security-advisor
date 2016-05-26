<?php

class Modules_SecurityWizard_Helper_WordPress
{
    public static function switchToHttps($wpId)
    {
        $db = pm_Bootstrap::getDbAdapter();
        $wordpress = $db->fetchRow("SELECT * FROM WordpressInstances WHERE id = ?", [$wpId]);
        if (false === $wordpress) {
            throw new pm_Exception("Instance with id = {$wpId} not found");
        }
        $allProperties = $db->fetchAll("SELECT * FROM WordpressInstanceProperties WHERE wordpressInstanceId = ?", [$wpId]);
        $properties = [];
        foreach ($allProperties as $p) {
            $properties[$p['name']] = $p['value'];
        }
        if (0 === strpos($properties['url'], 'https://')) {
            return;
        }

        $subscription = new Modules_SecurityWizard_Helper_Subscription($wordpress['subscriptionId']);
        $fileManager = new pm_FileManager($subscription->getPmDomain()->getId());

        $res = pm_ApiCli::callSbin('wpmng', [
            '--user=' . $subscription->getSysUser(),
            '--php=' . $subscription->getPhpCli(),
            '--',
            '--path=' . $fileManager->getFilePath($wordpress['path']),
            'search-replace',
            $properties['url'],
            str_replace('http://', 'https://', $properties['url']),
            '--skip-columns=guid',
        ]);
        if (0 !== $res['code']) {
            throw new pm_Exception('Cannot switch from HTTP to HTTPS: ' . $res['strout'] . $res['stderr']);
        }

        $request = <<<APICALL
        <wp-instance>
            <clear-cache>
                <filter>
                    <id>{$wpId}</id>
                </filter>
            </clear-cache>
        </wp-instance>
APICALL;
        $response = pm_ApiRpc::getService()->call($request);
        if ('error' == $response->{'wp-instance'}->{'clear-cache'}->result->status) {
            throw new pm_Exception('Cannot clear WordPress cache: ' . $response->{'wp-instance'}->{'clear-cache'}->result->errtext);
        }
    }
}
