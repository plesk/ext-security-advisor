<?php

class Modules_SecurityAdvisor_Helper_PanelCertificate
{
    const CERT_FILE = '/usr/local/psa/admin/conf/httpsd.pem';
    const RENEW_COMMAND = 'renew-hostname.php';

    public static function isPanelSecured()
    {
        $cert = (new pm_ServerFileManager)->fileGetContents(static::CERT_FILE);
        $certData = "";
        preg_match_all('/-----BEGIN (?<begin>.+?)-----(?<body>.+?)-----END (?<end>.+?)-----/is', $cert, $certParts);
        foreach ($certParts['begin'] as $key => $part) {
            if (0 != strcasecmp('CERTIFICATE', $part)) {
                continue;
            }
            $certData = "-----BEGIN CERTIFICATE-----{$certParts['body'][$key]}-----END CERTIFICATE-----\n{$certData}";
        }
        return static::verifyCertificate($certData);
    }

    public static function verifyCertificate($certData)
    {
        $caInfo = [
            pm_Context::getPlibDir() . 'resources/ca',
            pm_Context::getPlibDir() . 'resources/ca/cacert.pem',
            pm_Context::getPlibDir() . 'resources/ca/letsencrypt-root.pem', // for testing purpose
        ];
        $x509 = openssl_x509_read($certData);
        if (empty($x509)) {
            return false;
        }
        return (bool)openssl_x509_checkpurpose($x509, X509_PURPOSE_SSL_SERVER, $caInfo);
    }

    public static function isPanelHostname($hostname)
    {
        $cert = (new pm_ServerFileManager)->fileGetContents(static::CERT_FILE);
        if (!($ssl = openssl_x509_parse($cert))) {
            return false;
        }

        if (isset($ssl['extensions']['subjectAltName'])) {
            $san = explode(',', $ssl['extensions']['subjectAltName']);
        } else {
            $san = [];
        }
        $san = array_map('trim', $san);
        $san = array_map(function ($altName) {
            return 0 === strpos($altName, 'DNS:') ? substr($altName, strlen('DNS:')) : $altName;
        }, $san);
        foreach (array_merge([$ssl['subject']['CN']], $san) as $name) {
            if (0 == strcasecmp($name, $hostname)) {
                return true;
            }
        }
        return false;
    }

    public static function securePanel($hostname)
    {
        $res = pm_ApiCli::callSbin('letsencrypt-hostname.sh', [$hostname]);
        if ($res['code']) {
            throw new pm_Exception($res['stdout'] . $res['stderr']);
        }
        pm_Settings::set('secure-panel-hostname', $hostname);
        static::scheduleRenew();
    }

    public static function scheduleRenew()
    {
        foreach (pm_Scheduler::getInstance()->listTasks() as $task) {
            if (static::RENEW_COMMAND == $task->getCmd()) {
                return;
            }
        }

        $task = new pm_Scheduler_Task();
        $currentDayOfMonth = date('j');
        $task->setSchedule(array_merge(pm_Scheduler::$EVERY_MONTH, ['dom' => 28 > (int)$currentDayOfMonth ? $currentDayOfMonth : '28']));
        $task->setCmd(static::RENEW_COMMAND);

        pm_Scheduler::getInstance()->putTask($task);
    }

    public static function isDomainRegisteredInPlesk($domain)
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
