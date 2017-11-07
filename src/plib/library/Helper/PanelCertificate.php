<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

class Modules_SecurityAdvisor_Helper_PanelCertificate
{
    const RENEW_COMMAND = 'renew-hostname.php';

    protected $_certFile;

    public function __construct()
    {
        $this->_certFile = realpath(PRODUCT_ROOT_D . '/admin/conf/httpsd.pem');
    }

    public function isPanelSecured()
    {
        $cert = (new pm_ServerFileManager)->fileGetContents($this->_certFile);
        $certData = "";
        preg_match_all('/-----BEGIN (?<begin>.+?)-----(?<body>.+?)-----END (?<end>.+?)-----/is', $cert, $certParts);
        foreach ($certParts['begin'] as $key => $part) {
            if (0 != strcasecmp('CERTIFICATE', $part)) {
                continue;
            }
            $certData = "-----BEGIN CERTIFICATE-----{$certParts['body'][$key]}-----END CERTIFICATE-----\n{$certData}";
        }
        return Modules_SecurityAdvisor_Helper_Ssl::verifyCertificate($certData);
    }

    public function isPanelHostname($hostname)
    {
        $cert = (new pm_ServerFileManager)->fileGetContents($this->_certFile);
        foreach (Modules_SecurityAdvisor_Helper_Ssl::getCertificateSubjects($cert) as $name) {
            if (0 == strcasecmp($name, $hostname)) {
                return true;
            }
        }
        return false;
    }

    public static function securePanel($hostname)
    {
        $email = pm_Client::getByLogin('admin')->getProperty('email');
        $res = pm_ApiCli::callSbin('letsencrypt-hostname.sh', [$hostname, $email]);
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
