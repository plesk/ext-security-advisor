<?php

class Modules_SecurityWizard_Helper_PanelCertificate
{
    public static function isPanelSecured($hostname)
    {
        $url = 'https://' . $hostname . ':8443/check-plesk.php';

        $curlWithoutVerify = curl_init();
        curl_setopt ($curlWithoutVerify, CURLOPT_URL, $url);
        curl_setopt ($curlWithoutVerify, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($curlWithoutVerify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($curlWithoutVerify, CURLOPT_SSL_VERIFYHOST, false);
        $resultWithoutVerify = curl_exec($curlWithoutVerify);
        curl_close($curlWithoutVerify);

        $curlWithVerify = curl_init();
        curl_setopt ($curlWithVerify, CURLOPT_URL, $url);
        curl_setopt ($curlWithVerify, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($curlWithVerify, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt ($curlWithVerify, CURLOPT_SSL_VERIFYHOST, true);
        $resultWithVerify = curl_exec($curlWithVerify);
        curl_close($curlWithVerify);

        return (true === $resultWithVerify && $resultWithoutVerify == $resultWithVerify);
    }
}
