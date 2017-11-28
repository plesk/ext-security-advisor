<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

use PleskExt\SecurityAdvisor\Helper\FileSystem;

class Modules_SecurityAdvisor_Helper_Ssl
{
    /**
     * Check certificate with chain
     *
     * @param string $certData An X.509 certificate as string or a resource identifier
     * @param string $rootchainFile
     * @return bool
     */
    public static function verifyCertificate($certData, $rootchainFile = null)
    {
        $x509 = openssl_x509_read($certData);

        if (empty($x509)) {
            return false;
        }

        $result = !is_null($rootchainFile) && file_exists($rootchainFile)
            ? openssl_x509_checkpurpose($x509, X509_PURPOSE_ANY, static::_getCaInfo(), $rootchainFile)
            : openssl_x509_checkpurpose($x509, X509_PURPOSE_ANY, static::_getCaInfo());

        return (bool)$result;
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

    protected static function _getCaInfo()
    {
        static $caInfo = null;

        if (is_null($caInfo)) {
            $caInfo = array_filter([
                \pm_Context::getPlibDir() . 'resources/ca',
                \pm_Context::getPlibDir() . 'resources/ca/cacert.pem',
                \pm_Context::getPlibDir() . 'resources/ca/letsencrypt-root.pem', // for testing purpose
            ], 'file_exists');
        }

        return $caInfo;
    }
}
