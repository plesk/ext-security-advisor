<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
class Modules_SecurityAdvisor_Helper_Ssl
{
    public static function verifyCertificate($certData)
    {
        $caInfo = array_filter([
            pm_Context::getPlibDir() . 'resources/ca',
            pm_Context::getPlibDir() . 'resources/ca/cacert.pem',
            pm_Context::getPlibDir() . 'resources/ca/letsencrypt-root.pem', // for testing purpose
        ], 'file_exists');
        $x509 = openssl_x509_read($certData);
        if (empty($x509)) {
            return false;
        }
        return (bool)openssl_x509_checkpurpose($x509, X509_PURPOSE_ANY, $caInfo);
    }

    public static function getCertificateSubjects($certData)
    {
        $ssl = openssl_x509_parse($certData);
        if (isset($ssl['extensions']['subjectAltName'])) {
            $san = explode(',', $ssl['extensions']['subjectAltName']);
        } else {
            $san = [];
        }
        $san = array_map('trim', $san);
        $san = array_map(function ($altName) {
            return 0 === strpos($altName, 'DNS:') ? substr($altName, strlen('DNS:')) : $altName;
        }, $san);

        if (!in_array($ssl['subject']['CN'], $san)) {
            $san[] = $ssl['subject']['CN'];
        }
        return $san;
    }
}
