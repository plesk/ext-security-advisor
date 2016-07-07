<?php

class Modules_SecurityAdvisor_Helper_PanelCertificate
{
    public static function isPanelSecured()
    {
        $certFile = '/usr/local/psa/admin/conf/httpsd.pem';

        $cert = (new pm_ServerFileManager)->fileGetContents($certFile);
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

    public static function securePanel($hostname)
    {
        if (static::isDomainRegisteredInPlesk($hostname)) {
            Modules_SecurityAdvisor_Letsencrypt::run($hostname, true);
        } else {
            $res = pm_ApiCli::callSbin('letsencrypt-hostname.sh', [$hostname]);
            if ($res['code']) {
                throw new pm_Exception($res['stdout'] . $res['stderr']);
            }
        }
        pm_Settings::set('secure-panel-hostname', $hostname);
    }

    private function isDomainRegisteredInPlesk($domain)
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
