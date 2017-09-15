<?php
// Copyright 1999-2016. Parallels IP Holdings GmbH.

// post-install script to install telemetry client package
// extension controller
class DatagridCli
{

    // HTTP response codes
    private $_HTTP_OK                   = 200;
    private $_HTTP_FORBIDDEN            = 403;
    private $_HTTP_NOT_FOUND            = 404;
    private $_HTTP_SERVICE_UNAVAILABLE  = 503;

    // configuration
    private $_dgri_id_file        = '/etc/dgri.id';

    // misc
    private $_sleep_config        = 5;      // sec sleep for initial telemetry report
    private $_tout_url_contents   = 2;      // sec timeout to get tos URL contents
    private $_tout_activate       = 10;     // sec timeout for activation request
    private $_tout_evaluate       = 10;     // sec timeout for config evaluation
    private $_page_title          = '';


    // determine if the extension is active
    public function isActive()
    {
        if (pm_Settings::get('plxActivated') == '') {
            return false;
        }
        else {
            return true;
        }
    }

    // results action:  evaluate system and return results
    public function query($view = 'extended')
    {
        // init
        $msg = '';

        // collect telemetry only if configuration has changed
        $res = pm_ApiCli::callSbin('dgri-collect-nop.sh');
        // configuration has changed (non-zero => no change)
        if ($res['code'] == 0) {
        	// delay for processing
        	sleep(self._sleep_config); //TODO: process return value - wait extra
        }

        // --- make API call for config evaluation

        // get API URL and credentials
        $url = pm_Settings::get('apiUrl');
        $url = rtrim($url, '/') . '/';
        $username = pm_Settings::get('apiUsername');
        $password = pm_Settings::get('apiPassword');
        $dgri_id = trim(file_get_contents($this->_dgri_id_file));
        $url = $url . 'systems/' . $dgri_id . '?view=' . $view;
        // $url = $url . 'systems?system.id=' . $dgri_id . '&vuln.severity=min:5&view=normal';

        // call Datagrid API for system evaluation
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_tout_evaluate);
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // handle failure response
        if ($res === false || $code != $this->_HTTP_OK) {

            // handle 403 forbidden
            if ($code == $this->_HTTP_FORBIDDEN) {
                $msg = "Access to the Opsani service has
                    been denied.  If you have manually activated this extension,
                    please verify the Feed and API credentials you have entered
                    are correct.";

            // handle 404 not found
            } elseif ($code == $this->_HTTP_NOT_FOUND) {
                $msg = "The Opsani service cannot find the
                    resource for this Plesk system.  Please try again later.";

            // handle 503 service unavailable
            } elseif ($code == $this->_HTTP_SERVICE_UNAVAILABLE) {
                $msg = "The Opsani service is currently
                    unavailable.  Please try again later.";

            // handle all other response failures
            } else {
                $msg = "Failed to evaluate this Plesk system
                    using the Opsani service. HTTP response code $code.";
            }

        // check for unexpected (incomplete) response
        } else {
            // $res_decoded = json_decode($res, true);
            // if (! isset($res_decoded['vuln']) ) {
            //     $msg = "Failed to evaluate this Plesk system
            //         using the Datagrid service.  Response does not contain vulnerability
            //         or package information.";
            // }
        }

        // json encode error data for results page
        if ($msg !== '') {
            $msg = json_encode($msg);
        }
        // handle success
        else {
            $msg = $res;
        }

    	return $msg;
    }

    // process received data - count critical vulnerabilities
    public function count_critical($res, $min_sev = 8)
    {
        // $res is json-encoded data returned from API, system object, full view

        // parse json into assoc. array
        $res_decoded = json_decode($res, true);
        if (is_null($res_decoded)) {
            return json_encode('failed to decode API response');
        }

        // process data
        if (! isset($res_decoded['vulns']) ) {
            $res = 0;   // no known vulns
        } else {
            $res = $this->_count_critical($res_decoded['vulns'], $min_sev);
        }

        // encode response
        return json_encode($res);
    }

    private function _count_critical($vulns, $min_sev)
    {
        $count = 0;
        foreach ($vulns as $v) {
            //var_dump($v);
            try {
                $sev = (float)$v['severity'];
                //var_dump($v['id'], $sev);
                if ($sev >= $min_sev) {
                    $count++;
                }
            } catch (Exception $e) {
                // ignore
            }
        }
        return $count;
    }

    public function critical_packages($res, $min_sev = 8)
    {
        // $res is json-encoded data returned from API, system object, full view

        // parse json into assoc. array
        $res_decoded = json_decode($res, true);
        if (is_null($res_decoded)) {
            return json_encode('failed to decode API response');
        }

        // process data
        if (! isset($res_decoded['vulns']) ) {
            $res = "";   // no known vulnerable packages
        } else {
            $res = $this->_critical_packages($res_decoded['vulns'], $min_sev);
        }

        // encode response
        return json_encode($res);
    }

    private function _critical_packages($vulns, $min_sev)
    {
        $pkgs = [];
        foreach ($vulns as $v) {
            //var_dump($v);
            try {
                $sev = (float)$v['severity'];
                //var_dump($v['id'], $sev);
                if ($sev >= $min_sev) {
                    if (isset($v['packages'])) {
                        foreach ($v['packages'] as $p) {
                            $name = $p['name'];
                            if (isset($pkgs[$name])) {
                                $pkgs[$name]++;
                            } else {
                                $pkgs[$name] = 1;
                            }
                        };
                    }
                }
            } catch (Exception $e) {
                // ignore
            }
        }

        $pkglist = [];
        foreach ($pkgs as $p => $v) {
            $pkglist[] = $p;
            }
        return $pkglist;
    }

} // class DatagridCli


// init application context
pm_Context::init('dgri');

$dc = new DatagridCli();

if (! $dc->isActive() ) {
	echo 'Extension not active';
	exit(1);
}

$opt = '';
if ($argc > 1) {
    if ($argv[1] === 'critical-count' || $argv[1] === 'critical-packages') {
        $view = 'full';
        $opt  = $argv[1];
    } else {
        $view = $argv[1];
    }

    $ret = $dc->query($view);
} else {
    $ret = $dc->query();
}

if ($opt === 'critical-count') {
   $ret = $dc->count_critical($ret);
} elseif ($opt === 'critical-packages') {
   $ret = $dc->critical_packages($ret);
}

echo $ret;

exit(0);
