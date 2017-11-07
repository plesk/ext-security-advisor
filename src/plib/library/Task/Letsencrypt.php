<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

namespace PleskExt\SecurityAdvisor\Task;

class Letsencrypt extends \pm_LongTask_Task
{
    public $trackProgress = true;

    public function getId()
    {
        return 'letsencrypt';
    }

    public function run()
    {
        $domainIds = $this->getParam('domainIds');
        $total = count($domainIds);
        $processed = 0;
        $errors = [];
        \pm_Settings::set('longtask-letsencrypt-progress', 0);
        foreach ($domainIds as $domainId) {
            try {
                \Modules_SecurityAdvisor_Letsencrypt::runDomain(new \pm_Domain($domainId));
            } catch (\pm_Exception $e) {
                $errors[] = $e->getMessage();
            }
            $progress = floor(100 * ++$processed / $total);
            $this->updateProgress($progress);
            \pm_Settings::set('longtask-letsencrypt-progress', $progress);
        }
        $this->setParam('errors', $errors);
        $this->updateProgress(100);
        \pm_Settings::clean('longtask-letsencrypt-progress');
    }

    public function statusMessage()
    {
        $status = $this->getParam('errors') ? 'error' : $this->getStatus();
        return \pm_Locale::lmsg('list.domains.longTask.' . $status);
    }
}
