<?php
// Copyright (c) 2016 Datagrid Systems, Inc.  All Rights Reserved.  See the
// LICENSE file installed with this extension for licensing information.

$contents = <<<EOT
        <!-- STYLES -->
        <link rel="stylesheet" type="text/css" href="$base_url/css/styles-secw.css" />

        <script type="text/javascript">
            function show_busy(eid) {
                var e = document.getElementById(eid);
                e.innerHTML = '<img src="$base_url/images/busy32.gif" width="24px" height="24px" />';
            }
        </script>

        <div class="secw-tab-content">
            <div class="secw-settings">
                <form method="post" id="secw-settings-form" class="secw-settings-form">
                    <table class="secw-settings-table">

                        <tr>
                            <td>
                                <img src="$base_url/images/logo-http2.png" alt="http2-logo" width="60px" height="60px" />
                            </td>
                            <td id='secw-http2-state'>
                                $http2_state
                            </td>
                            <td>
                                $http2_content
                            </td>
                            <td>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <img src="$base_url/images/logo-datagrid.png" alt="datagrid-logo" width="60px" height="60px" />
                            </td>
                            <td id='secw-datagrid-state'>
                                $datagrid_state
                            </td>
                            <td>
                                $datagrid_content
                            </td>
                            <td>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <img src="$base_url/images/logo-patchman.png" alt="patchman-logo" width="60px" height="60px" />
                            </td>
                            <td id='secw-patchman-state'>
                                $patchman_state
                            </td>
                            <td>
                                $patchman_content
                            </td>
                            <td>
                            </td>
                        </tr>

                    </table>
                </form>
            </div>
        </div>
EOT;

echo $contents;
?>