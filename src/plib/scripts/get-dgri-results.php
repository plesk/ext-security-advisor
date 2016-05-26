<?php
// Copyright (c) 2016 Datagrid Systems, Inc.  All Rights Reserved.  See the
// LICENSE file installed with this extension for licensing information.

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

    // results action:  evaluate system and display results
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
        echo $url;

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
                $msg = "Access to the Datagrid service has
                    been denied.  If you have manually activated this extension,
                    please verify the Feed and API credentials you have entered
                    are correct.";

            // handle 404 not found
            } elseif ($code == $this->_HTTP_NOT_FOUND) {
                $msg = "The Datagrid service cannot find the
                    resource for this Plesk system.  Please try again later.";

            // handle 503 service unavailable
            } elseif ($code == $this->_HTTP_SERVICE_UNAVAILABLE) {
                $msg = "The Datagrid service is currently
                    unavailable.  Please try again later.";

            // handle all other response failures
            } else {
                $msg = "Failed to evaluate this Plesk system
                    using the Datagrid service.  HTTP response code $code.";
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
} // class DatagridCli


// init application context
pm_Context::init('dgri');

$dc = new DatagridCli();

if (! $dc->isActive() ) {
	echo 'Extension not active';
	exit(1);
}

if ($argc > 1) {
   $ret = $dc->query($argv[1]);
} else {
   $ret = $dc->query();
}

echo $ret;

exit(0);
