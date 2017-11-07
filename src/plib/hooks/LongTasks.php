<?php
// Copyright 1999-2017. Plesk International GmbH. All rights reserved.

use PleskExt\SecurityAdvisor\Task\Letsencrypt;

class Modules_SecurityAdvisor_LongTasks extends \pm_Hook_LongTasks
{
    public function getLongTasks()
    {
        return [new Letsencrypt()];
    }
}
