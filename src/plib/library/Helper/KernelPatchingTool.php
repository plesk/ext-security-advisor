<?php
// Copyright 1999-2017. Parallels IP Holdings GmbH.
class Modules_SecurityAdvisor_Helper_KernelPatchingTool
{
    private $_tools;

    /**
     * @return Modules_SecurityAdvisor_KernelPatchingTool_Interface[]
     */
    private function _getTools()
    {
        if (is_null($this->_tools)) {
            $this->_tools = [
                new Modules_SecurityAdvisor_KernelPatchingTool_VirtuozzoReadykernel(),
                new Modules_SecurityAdvisor_KernelPatchingTool_KernelCare(),
            ];
        }
        return $this->_tools;
    }

    /**
     * Check if any patching tool installed on this Plesk server and return true if yes, false otherwise
     *
     * @return bool
     */
    public function isAnyInstalled()
    {
        foreach ($this->_getTools() as $tool) {
            if ($tool->isInstalled()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if any patching tool available on this Plesk server and return true if yes, false otherwise
     *
     * @return bool
     */
    public function isAnyAvailable()
    {
        foreach ($this->_getTools() as $tool) {
            if ($tool->isAvailable()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Return true, if several different patching tools could be installed on this Plesk
     * server simultaneously, false otherwise
     *
     * @return bool
     */
    public function isSeveralAvailable()
    {
        $isAnyAvailable = false;
        foreach ($this->_getTools() as $tool) {
            if ($tool->isAvailable()) {
                if ($isAnyAvailable) {
                    return true;
                }
                $isAnyAvailable = true;
            }
        }
        return false;
    }

    /**
     * Retrieve list of patching tools which could be installed on this Plesk server
     *
     * @return Modules_SecurityAdvisor_KernelPatchingTool_Interface[]
     */
    public function getAvailable()
    {
        $result = [];
        foreach ($this->_getTools() as $tool) {
            if ($tool->isAvailable()) {
                $result[] = $tool;
            }
        }
        return $result;
    }

    /**
     * Retrieve first (in the order of definition, see _getTools methid for details) patching tool
     * which could be installed on this Plesk server
     *
     * @return Modules_SecurityAdvisor_KernelPatchingTool_Interface
     */
    public function getFirstAvailable()
    {
        $tools = $this->getAvailable();
        return array_shift($tools);
    }

    /**
     * Retrieve list of patching tool which could be installed on this Plesk server except
     * first one (in the order of definition, see _getTools methid for details)
     *
     * @return Modules_SecurityAdvisor_KernelPatchingTool_Interface[]
     */
    public function getRestAvailable()
    {
        $tools = $this->getAvailable();
        array_shift($tools);
        return $tools;
    }

    /**
     * Retrieve list of patching tools installed on this Plesk server
     *
     * @return Modules_SecurityAdvisor_KernelPatchingTool_Interface[]
     */
    public function getInstalled()
    {
        $result = [];
        foreach ($this->_getTools() as $tool) {
            if ($tool->isInstalled()) {
                $result[] = $tool;
            }
        }
        return $result;
    }

    /**
     * Retrieve release name of Linux kernel installed on this server
     *
     * @return string
     */
    public function getKernelRelease()
    {
        $kernelRelease = function_exists('posix_uname')
            ? posix_uname()['release']
            : php_uname('r');
        return $kernelRelease;
    }
}
