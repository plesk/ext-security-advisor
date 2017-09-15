<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
$messages = [
    'pageTitle' => 'Security Advisor',
    'tabs.domains' => 'Domains',
    'tabs.wordpress' => 'WordPress',
    'tabs.system' => 'System',
    'list.domains.domainNameColumn' => 'Domain',
    'list.domains.statusColumn' => 'S',
    'list.domains.statusInsecure' => 'Not secure',
    'list.domains.statusInvalid' => 'The certificate is either self-signed or not valid',
    'list.domains.statusLetsencrypt' => 'Secured with a Let\'s Encrypt certificate',
    'list.domains.statusOk' => 'Secured with an SSL/TLS certificate',
    'list.domains.validFromColumn' => 'Valid From',
    'list.domains.validToColumn' => 'Valid To',
    'list.domains.sanColumn' => 'Alternative Names',
    'list.domains.search.status.column' => 'Status',
    'list.domains.search.status.any' => 'Any',
    'list.domains.search.status.secure' => 'Secure',
    'list.domains.search.status.insecure' => 'Insecure',
    'list.domains.letsencryptDomains' => 'Secure with free SSL/TLS certificate',
    'list.domains.letsencryptDomainsDescription' => 'Install SSL/TLS certificates for the selected domains.',
    'list.domains.installLetsencrypt' => 'Install Let\'s Encrypt',
    'list.domains.installLetsencryptDescription' => 'The Let\'s Encrypt extension is required for SSL/TLS certificates creation.',
    'list.symantec.button.purchase' => 'Secure',
    'list.symantec.button.purchaseExtended' => 'Upgrade',
    'list.symantec.button.purchaseHint' => 'Purchase an SSL/TLS Certificate',
    'list.wordpress.nameColumn' => 'Name',
    'list.wordpress.urlColumn' => 'URL',
    'list.wordpress.httpsColumn' => 'HTTPS',
    'list.wordpress.httpsEnableTitle' => 'Secure',
    'list.wordpress.httpsDisableTitle' => 'Insecure',
    'list.wordpress.switchToHttpsButtonTitle' => 'Switch to HTTPS',
    'list.wordpress.switchToHttpsButtonDesc' => '',
    'list.wordpress.notAllowed' => 'WordPress Toolkit is not allowed by the license key.',
    'list.wordpress.notInstalled' => 'The WordPress Toolkit extension is not installed.',
    'list.wordpress.installWpToolkit' => 'Install WordPress Toolkit',
    'list.wordpress.installWpToolkitDescription' => 'The WordPress Toolkit extension is required for WordPress instances management.',
    'form.settings.securePaneldesc' => 'Replacing the self-signed SSL/TLS certificate with a free certificate from Let\'s Encrypt secures connections to Plesk and removes the "Untrusted Site" warning.',
    'form.settings.securePanelHostnametitle' => 'Hostname',
    'form.settings.http2title' => 'HTTP/2',
    'form.settings.http2desc' => 'Enabling HTTP/2 will give a significant speed boost to all SSL/TLS-secured websites on the server.',
    'controllers.domains-list.free-ssl.successMsg' => 'Free SSL certificate was successfully installed on %%domains%%.',
    'controllers.letsencrypt.successMsg' => 'Let\'s Encrypt SSL certificate was successfully installed on %%domains%%.',
    'controllers.letsencrypt.inProgressMsg' => 'Let\'s Encrypt is currently working: %%progress%%% complete.',
    'controllers.securePanel.pageTitle' => 'Secure Plesk',
    'controllers.securePanel.save.successMsg' => 'The settings were successfully applied',
    'controllers.switchWordpressToHttps.successMsg' => 'All selected WordPress instances were switched to HTTPS',
    'controllers.switchWordpressToHttps.errorMsg' => 'There were issues with switching WordPress instances to HTTPS: ',
    'promo.title' => 'Security Advisor',
    'promo.textDomains' => 'Your next step is: Secure your domains (%%count%% domains are not secure)',
    'promo.textWordpress' => 'Your next step is: Secure your WordPress installations (%%count%% installations are not secure).',
    'promo.textHttp2' => 'Your next step is: Enable HTTP/2 for all domains.',
    'promo.textPanel' => 'Your next step is: Secure Plesk with an SSL certificate.',
    'promo.textKernelPatchingTool' => 'Your next step is: Keep your system kernel up-to-date with the rebootless kernel patching tool.',
    'promo.textDatagrid' => 'Your next step is: Keep your system up-to-date with Datagrid reliability and vulnerability scanner.',
    'promo.textPatchman' => 'Your next step is: Keep applications up-to-date and secure them with Patchman.',
    'promo.textGoogleauthenticator' => 'Your next step is: Activate 2 Factor Authentication (2FA) with Google Authenticator.',
    'promo.textDone' => 'Plesk has been secured!',
    'promo.buttonSecure' => 'Secure it!',
    'promo.buttonDone' => 'Check it out',

    'controllers.symantec.please-install' => 'Please %%link%%',
    'controllers.symantec.please-install.link-text' => 'install the Symantec SSL extension',
    'controllers.symantec.not-available' => 'The Symantec SSL extension is not supported in your Plesk version. Upgrade to the latest Plesk version to use the Symantec SSL extension.',

    'controllers.system.panelSecured' => 'Plesk is secured with a valid SSL/TLS certificate',
    'controllers.system.panelNotSecured' => 'Plesk is not secured with a valid SSL/TLS certificate',
    'controllers.system.secureDesc' => 'Secure Plesk with Let\'s Encrypt',
    'controllers.system.letsencryptDesc' => 'Let\'s Encrypt is a certificate authority (CA) that allows you to create free SSL/TLS certificates for your domains.',
    'controllers.system.letsencryptInstall' => 'Install Let\'s Encrypt and secure Panel with free SSL certificate',
    'controllers.system.http2Desc' => 'HTTP/2 improves performance; specifically, end-user perceived latency, network and server resource usage.',
    'controllers.system.http2Enabled' => 'HTTP/2 is enabled',
    'controllers.system.http2Button' => 'Enable HTTP/2',
    'controllers.system.nginxDesc' => 'Enable nginx so that HTTP/2 can be turned on. HTTP/2 improves performance; specifically, end-user perceived latency, network and server resource usage.',
    'controllers.system.nginxButton' => 'Enable nginx reverse proxy and HTTP/2',
    'controllers.system.aiDesc' => 'Install nginx to make HTTP/2 available. To install nginx, select nginx web server in the Web hosting group in Plesk Installer. HTTP/2 improves performance; specifically, end-user perceived latency, network and server resource usage.',
    'controllers.system.aiButton' => 'Install nginx web server and enable HTTP/2',
    'controllers.system.datagridDesc' => 'The Datagrid scanner analyzes your server configuration and compares it to real world results from servers around the world to report reliability and security vulnerabilities.  On top of that, it\'s free.',
    'controllers.system.datagrid' => 'Datagrid reliability and vulnerability scanner',
    'controllers.system.datagridActivate' => 'Activate the Datagrid reliability and vulnerability scanner',
    'controllers.system.datagridInstall' => 'Install the Datagrid reliability and vulnerability scanner',
    'controllers.system.patchmanDesc' => 'Patchman automatically and safely patches vulnerabilities in CMSs like WordPress, Joomla, and Drupal. On top of that, it cleans up malware.',
    'controllers.system.patchman' => 'Patchman',
    'controllers.system.patchmanActivate' => 'Activate Patchman',
    'controllers.system.patchmanInstall' => 'Install Patchman',
    'controllers.system.googleauthenticatorDesc' => 'Google Authenticator adds a 2 Factor Authentication (2FA) to the login of your Plesk instance.',
    'controllers.system.googleauthenticator' => 'Google Authenticator',
    'controllers.system.googleauthenticatorActivate' => 'Activate Google Authenticator',
    'controllers.system.googleauthenticatorInstall' => 'Install Google Authenticator',
    'controllers.system.symantecDesc' => 'Symantec SSL is a certificate authority (CA) that allows you to purchase commercial SSL/TLS certificates for your domains.',
    'controllers.system.symantec' => 'Symantec SSL',
    'controllers.system.symantecActivate' => 'Activate Symantec SSL',
    'controllers.system.symantecInstall' => 'Install Symantec SSL',

    'controllers.system.kernelPatchingToolInstall' => 'Install %%name%%',
    'controllers.system.kernelPatchingToolInstalled' => 'Your kernel is kept up to date with %%name%% tool',
    'controllers.system.kernelPatchingToolSeveralDescription' => 'Your system Linux kernel version is %%kernelRelease%%. You can install one of the rebootless kernel patching tools to keep it up-to-date.',
    'controllers.system.kernelPatchingToolSeveralWarning' => 'Warning: we strongly recommend to use only one of the tools.',
    'controllers.system.kernelPatchingToolSingleDescription' => 'Your system Linux kernel version is %%kernelRelease%%. You can install the rebootless kernel patching tools to keep it up-to-date.',
    'controllers.system.virtuozzoReadyKernelDescription' => 'The Virtuozzo ReadyKernel is a kpatch-based service that offers a more convenient, rebootless alternative to updating the kernel the usual way and allows you to apply critical security updates without having to wait for scheduled server downtime. There is also no need to reboot the server after installing ReadyKernel patches or tools.',
    'controllers.system.kernelcareDescription' => 'The KernelCare removes the need to reboot the server, by automatically patching any security vulnerabilities in the kernel without the need for reboot.',
    'controllers.system.kernelPatchingToolInstallError' => 'Unable to install kernel patching tool "%%kernelPatchingToolName%%": %%errorMessage%%',

    'controllers.system.stateEnabled' => 'Enabled',
    'controllers.system.stateDisabled' => 'Disabled',
    'controllers.system.stateRunning' => 'Running',
    'controllers.system.stateInstalled' => 'Installed',
    'controllers.system.stateNotActivated' => 'Not Activated',
    'controllers.system.stateNotInstalled' => 'Not Installed',
    'controllers.system.busy' => 'Please wait...',

    'subscription.title' => 'Security Advisor for subscription "%%name%%"',

    'custom.button.title' => 'Security Advisor',
    'custom.button.description' => 'Secure your subscription',
];
