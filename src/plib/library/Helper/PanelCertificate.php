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

    /**
     * Check Panel is secured with correct sertificate
     * @return bool
     */
    public function isPanelSecured()
    {
        $panelCert = $this->_getSslCertAndChain(
            \Modules_SecurityAdvisor_Helper_Utils::getHostname(),
            \Modules_SecurityAdvisor_Helper_Utils::getPanelPort());

        return \Modules_SecurityAdvisor_Helper_Ssl::verifyCertificate($panelCert['cert'], $panelCert['chain']);
    }

    /**
     * Fetch X509 certificate and chain from specified address.
     *
     * No certificate verification is performed.
     *
     * @param string $address IP address
     * @param int $port
     * @param float $timeout Connections timeout in seconds
     * @return array The 'cert' element of the array is a resource identifier of domain's X509 certificate data.
     *               The 'chain' element is array of chain resource identifiers.
     * @throws pm_Exception
     */
    protected function _getSslCertAndChain($address, $port = 443, $timeout = 30.0)
    {
        $stream = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'capture_peer_cert' => true,
                'capture_peer_cert_chain' => true,
            ],
        ]);

        $socketAddress = "ssl://{$address}:{$port}";
        $client = @stream_socket_client($socketAddress, $errNo, $errStr, $timeout, STREAM_CLIENT_CONNECT, $stream);
        if ($client === false) {
            throw new \pm_Exception("Failed to open '{$socketAddress}': {$errStr}", $errNo);
        }
        $ssl = stream_context_get_params($client)['options']['ssl'];

        return [
            'cert' => $ssl['peer_certificate'],
            'chain' => $ssl['peer_certificate_chain'],
        ];
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
