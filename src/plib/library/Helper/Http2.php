<?php

class Modules_SecurityAdvisor_Helper_Http2
{
    public static function isHttp2Enabled()
    {
        $root_d = PRODUCT_ROOT;
        $panel_conf = $root_d . '/admin/conf/panel.ini';
        $param="nginxHttp2";
        $section="webserver";

        if (! file_exists($panel_conf)) {
            return false;
        }
        $conf = parse_ini_file($panel_conf, true);
        if ($conf === false) {
            return false;
        }
        if (! isset($conf[$section]) || ! isset($conf[$section][$param]) || ! $conf[$section][$param]) {
            return false;
        }

        return true;
    }
}
