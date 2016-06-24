<?php

class Modules_SecurityWizard_Helper_PanelCertificate
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
        $caInfo = [pm_Context::getPlibDir() . 'resources/ca'];
        $x509 = openssl_x509_read($certData);
        if (empty($x509)) {
                return false;
        }
        return (bool)openssl_x509_checkpurpose($x509, X509_PURPOSE_SSL_SERVER, $caInfo);
    }
}
