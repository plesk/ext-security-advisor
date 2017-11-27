<?php
// Copyright 1999-2017. Plesk International GmbH. All rights reserved.

namespace PleskExt\SecurityAdvisor\Helper;

class FileSystem
{
    public static function getTempDir()
    {
        $tmpDir = \pm_Context::getVarDir() . 'tmp/';
        static::ensureDirExists($tmpDir);

        return $tmpDir;
    }

    /**
     * Makes directory if it is not exist.
     *
     * @see mkdir() for details.
     *
     * @param string $pathname The directory path.
     * @param int $mode The file mode for newly created directories.
     * @param bool $recursive Allows the creation of nested directories specified in the pathname.
     * @throws \pm_Exception on failure.
     */
    public static function ensureDirExists($pathname, $mode = 0777, $recursive = true)
    {
        if (!file_exists($pathname) && !mkdir($pathname, $mode, $recursive)) {
            throw new \pm_Exception("Can not create directory '${pathname}'.");
        }
    }

    /**
     * Create file with unique file name and set its permissions.
     *
     * The file will be created with `0600` permission mode.
     *
     * @see tempnam() for details.
     *
     * @param string $tmpDir The directory where the temporary filename will be created.
     * @param string $prefix The prefix of the generated temporary filename.
     * @return string Returns the new temporary filename (with path).
     * @throws \pm_Exception on failure.
     */
    public static function makeTempFile($tmpDir, $prefix)
    {
        $tmpFile = tempnam($tmpDir, $prefix);
        if ($tmpFile === false) {
            throw new \pm_Exception("Failed to create temporary file in directory '{$tmpDir}' and prefix '{$prefix}'.");
        }

        return $tmpFile;
    }
}
