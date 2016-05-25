<?php

class Modules_SecurityWizard_Letsencrypt
{
    const INSTALL_URL = 'https://ext.plesk.com/packages/f6847e61-33a7-4104-8dc9-d26a0183a8dd-letsencrypt/download';

    public static function run($domainName, $securePanel = false)
    {
        $options = ['-d', $domainName];
        if ($securePanel) {
            $options[] = '--letsencrypt-plesk:plesk-secure-panel';
        }
        $result = pm_ApiCli::callSbin('letsencrypt.sh', $options);
    }
}
