<?php
// Copyright (c) 2016 Datagrid Systems, Inc.  All Rights Reserved.  See the
// LICENSE file installed with this extension for licensing information.

$contents = <<<EOT
        <!-- STYLES -->
        <link rel="stylesheet" type="text/css" href="$base_url/css/styles-secw.css" />

        <div class="secw-tab-content">
            <div class="secw-settings">
                <form method="post" id="secw-settings-form" class="secw-settings-form">
                    <table class="secw-settings-table">
                        <tr>
                            <td>
                                <img src="$base_url/images/http2-64x64.png" alt="http2-logo" width="32px" height="32px" />
                            </td>
                            <td>
                                HTTP2
                            </td>
                            <td>
                                <input type="submit" name="$btn_http2_name" value="$btn_http2_value" class="small button" />
                            </td>
                        </tr>

                    </table>
                </form>
            </div>
        </div>
EOT;

echo $contents;
?>