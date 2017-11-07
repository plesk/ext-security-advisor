<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

use \PleskExt\SecurityAdvisor\Task\Letsencrypt;

class Modules_SecurityAdvisor_Helper_Async
{
    const STATUS_NEW = 'new';
    const STATUS_DONE = 'done';
    const STATUS_ERROR = 'error';

    public function __construct(array $items)
    {
        $this->items = array_fill_keys($items, static::STATUS_NEW);
    }

    public function runLetsencrypt()
    {
        $this->save();
        $domainIds = array_map('intval', array_keys($this->items));
        if (static::hasLongTasks()) {
            $task = new Letsencrypt();
            $task->setParam('domainIds', $domainIds);
            (new \pm_LongTask_Manager())->start($task);
        } else {
            \pm_ApiCli::callSbin('letsencrypt-async.sh', $domainIds);
        }
    }

    public function save()
    {
        pm_Settings::set('async', json_encode($this->items, JSON_FORCE_OBJECT));
    }

    public function done($item)
    {
        $this->items[$item] = static::STATUS_DONE;
        $this->save();
    }

    public function error($item, $error)
    {
        $this->items[$item] = static::STATUS_ERROR;
        $error = substr($error, 0, 2000);
        pm_Settings::set('async-error-' . $item, $error);
        $this->save();
    }

    public static function hasLongTasks()
    {
        return class_exists('\pm_Hook_LongTasks');
    }

    public static function progress()
    {
        if (static::hasLongTasks()) {
            $progress = \pm_Settings::get('longtask-letsencrypt-progress', 100);
            return ['progress' => $progress];
        }

        $items = (array)json_decode(pm_Settings::get('async', '{}'), true);
        $domains = $errors = $newItems = [];
        foreach ($items as $item => $status) {
            if (static::STATUS_DONE === $status) {
                try {
                    $domains[$item] = (new pm_Domain($item))->getName();
                } catch (pm_Exception $e) {
                    continue;
                }
            } elseif (static::STATUS_ERROR === $status) {
                if ($error = pm_Settings::get('async-error-' . $item)) {
                    $errors[$item] = $error;
                }
            } elseif (static::STATUS_NEW === $status) {
                $newItems[$item] = true;
            }
        }

        return [
            'progress' => count($items) > 0
                ? floor(100 * (count($items) - count($newItems)) / count($items))
                : 100,
            'domains' => $domains,
            'errors' => $errors,
        ];
    }

    public static function close($status, $item)
    {
        if (static::STATUS_ERROR === $status) {
            pm_Settings::set('async-error-' . (int)$item, '');
            return;
        }

        $items = (array)json_decode(pm_Settings::get('async', '{}'), true);
        if (static::STATUS_DONE === $status) {
            $items = array_filter($items, function ($status) {
                return static::STATUS_DONE !== $status;
            });
        }

        if ('any' === $status) {
            $pid = (int)pm_Settings::get('async-pid');
            if ($pid && false !== posix_getsid($pid)) {
                pm_Log::debug("Kill process {$pid}");
                posix_kill($pid, SIGKILL);
            }
            $items = [];
        }
        pm_Settings::set('async', json_encode($items, JSON_FORCE_OBJECT));
    }
}
