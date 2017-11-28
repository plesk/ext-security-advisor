<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

use PleskExt\SecurityAdvisor\Helper\FileSystem;

class Modules_SecurityAdvisor_Helper_PanelCertificate
{
    const RENEW_COMMAND = 'renew-hostname.php';

    protected $_certFile;

    public function __construct()
    {
        $this->_certFile = realpath(PRODUCT_ROOT_D . '/admin/conf/httpsd.pem');
        $this->_rootchainFile = realpath(PRODUCT_ROOT_D . '/admin/conf/rootchain.pem');
    }

    /**
     * Check Panel is secured with correct sertificate
     * @return bool
     */
    public function isPanelSecured()
    {
        $chain = $this->_getCertificatesFromFile($this->_certFile);
        if (empty($chain)) {
            return false;
        }
        $certToCheck = array_shift($chain);

        $chain = array_unique(array_merge($chain, $this->_getCertificatesFromFile($this->_rootchainFile)));

        if (empty($chain)) {
            $result = \Modules_SecurityAdvisor_Helper_Ssl::verifyCertificate($certToCheck);
        } else {
            try {
                $chainTmp = FileSystem::makeTempFile(FileSystem::getTempDir(), 'ca-');
                file_put_contents($chainTmp, implode("\n", $chain));
                $result = \Modules_SecurityAdvisor_Helper_Ssl::verifyCertificate($certToCheck, $chainTmp);
            } finally {
                if (isset($chainTmp)) {
                    unlink($chainTmp);
                }
            }
        }

        return $result;
    }

    /**
     * Returns list of correct certificates from input file
     *
     * @param $certFileName
     * @return string[]
     */
    protected function _getCertificatesFromFile($certFileName)
    {
        $fileManager = new pm_ServerFileManager();
        if (!$fileManager->fileExists($certFileName)) {
            return [];
        }

        return $this->_getCertificatesFromPem($fileManager->fileGetContents($certFileName));
    }

    /**
     * @param string $pemData
     * @return string[]
     */
    protected function _getCertificatesFromPem($pemData)
    {
        $result = [];

        preg_match_all('/-----BEGIN (?<begin>.+?)-----(?<body>.+?)-----END (?<end>.+?)-----/is', $pemData, $certParts);
        foreach ($certParts['begin'] as $key => $part) {
            if (0 != strcasecmp('CERTIFICATE', $part)) {
                continue;
            }
            $pemData = "-----BEGIN CERTIFICATE-----{$certParts['body'][$key]}-----END CERTIFICATE-----";
            $x509 = openssl_x509_read($pemData);
            if (!empty($x509) && openssl_x509_export($x509, $output)) {
                $result[] = $output;
            }
        }

        return $result;
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
