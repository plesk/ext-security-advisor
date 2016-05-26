<?php

class Modules_SecurityWizard_Helper_Http2
{
    public static function isHttp2Enabled()
    {
        $panelConf = PRODUCT_ROOT . '/admin/conf/panel.ini';
        $param="nginxHttp2";
        $section="webserver";

        if (!file_exists($panel_conf)) {
            return false;
        }
        $conf = parse_ini_file($panel_conf, true);
        if ($conf === false) {
            return false;
        }
        if (!isset($conf[$section]) || ! isset($conf[$section][$param]) || !$conf[$section][$param]) {
            return false;
        }

        return true;
    }
}
