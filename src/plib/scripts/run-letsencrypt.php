<?php

$moduleId = basename(dirname(dirname(__FILE__)));
pm_Context::init($moduleId);

$pid = posix_getpid();
pm_Log::debug("Async process pid {$pid}");
pm_Settings::set('async-pid', $pid);

$domainIds = array_slice($argv, 1);
$async = new Modules_SecurityAdvisor_Helper_Async($domainIds);
foreach ($domainIds as $domainId) {
    try {
        $domain = new pm_Domain($domainId);
        Modules_SecurityAdvisor_Letsencrypt::run($domain->getName());
        $async->done($domainId);
    } catch (pm_Exception $e) {
        $async->error($domainId, $e->getMessage());
    }
}
