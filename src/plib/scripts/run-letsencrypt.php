<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.
$moduleId = basename(dirname(dirname(__FILE__)));
pm_Context::init($moduleId);

$pid = posix_getpid();
pm_Log::debug("Async process pid {$pid}");
pm_Settings::set('async-pid', $pid);

$domainIds = array_slice($argv, 1);
$async = new Modules_SecurityAdvisor_Helper_Async($domainIds);
foreach ($domainIds as $domainId) {
    try {
        Modules_SecurityAdvisor_Letsencrypt::runDomain(new pm_Domain($domainId));
        $async->done($domainId);
    } catch (pm_Exception $e) {
        $async->error($domainId, $e->getMessage());
    }
}
