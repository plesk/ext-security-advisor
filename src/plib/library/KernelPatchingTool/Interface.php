<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH.

interface Modules_SecurityAdvisor_KernelPatchingTool_Interface
{
    /**
     * Check if patching tool could be installed on this server
     *
     * @return boolean
     */
    public function isAvailable();

    /**
     * Check if patching tool already installed on this server
     *
     * @return boolean
     */
    public function isInstalled();

    /**
     * Check if patching tool is activated and working on this server
     *
     * @return boolean
     */
    public function isActive();

    /**
     * Retrieve unique system name (identity) of patching tool
     *
     * @return string
     */
    public function getName();

    /**
     * Retrieve patching tool name which will be visible in user interface
     *
     * @return string
     */
    public function getDisplayName();

    /**
     * Retrieve file name of patching tool logo
     *
     * @return string
     */
    public function getLogoFileName();

    /**
     * Retrieve URL whilch could be used to install patching tool module in Plesk
     *
     * @return string
     */
    public function getInstallUrl();

    /**
     * Retrieve patching tool description which will, be visible in user interface
     *
     * @return string
     */
    public function getDescription();
}
