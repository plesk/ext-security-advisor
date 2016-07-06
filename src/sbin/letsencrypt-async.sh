#!/bin/bash -e

plesk bin extension --exec security-advisor run-letsencrypt.php $@ 1>/dev/null 2>&1 &
