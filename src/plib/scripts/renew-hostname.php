<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
$moduleId = basename(dirname(dirname(__FILE__)));
pm_Context::init($moduleId);

$hostname = pm_Settings::get('secure-panel-hostname');
if (!$hostname) {
    return;
}
$panelCertificate = new Modules_SecurityAdvisor_Helper_PanelCertificate();
if (!$panelCertificate->isPanelHostname($hostname)) {
    return;
}
if ($panelCertificate->isDomainRegisteredInPlesk($hostname)) {
    return;
}
$panelCertificate->securePanel($hostname);
