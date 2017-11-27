<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

use PleskExt\SecurityAdvisor\Helper\FileSystem;

class Modules_SecurityAdvisor_Helper_Ssl
{
    /**
     * Check certificate with chain
     *
     * @param string|resource $x509 An X.509 certificate as string or a resource identifier
     * @param resource[] $inputChain
     * @return bool
     */
    public static function verifyCertificate($x509, $inputChain = [])
    {
        if (is_string($x509)) {
            $x509 = openssl_x509_read($x509);
        }

        if (empty($x509)) {
            return false;
        }

        $chain = array_reduce($inputChain, function ($carry, $cert) {
            if (openssl_x509_export($cert, $certData)) {
                $carry[] = $certData;
            }
            return $carry;
        }, []);

        if (empty($chain)) {
            $result = openssl_x509_checkpurpose($x509, X509_PURPOSE_ANY, static::_getCaInfo());
        } else {
            try {
                $caTmp = FileSystem::makeTempFile(FileSystem::getTempDir(), 'ca-');
                file_put_contents($caTmp, implode("\n", $chain));
                $result = openssl_x509_checkpurpose($x509, X509_PURPOSE_ANY, static::_getCaInfo(), $caTmp);
            } finally {
                if (isset($caTmp)) {
                    unlink($caTmp);
                }
            }
        }

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
                realpath(\pm_Context::getPlibDir() . 'resources/ca'),
                realpath(\pm_Context::getPlibDir() . 'resources/ca/cacert.pem'),
                realpath(\pm_Context::getPlibDir() . 'resources/ca/letsencrypt-root.pem'), // for testing purpose
            ], 'file_exists');
        }

        return $caInfo;
    }
}
