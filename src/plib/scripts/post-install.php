<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH. All Rights Reserved.

require_once __DIR__ . '/../vendor/autoload.php';

\pm_Context::init('security-advisor');

try {
    (new PleskExt\SecurityAdvisor\Installer())->installHomeAdminCustomButton();
} catch (Exception $e) {
    \pm_Log::err($e);
}
