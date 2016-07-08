#!/bin/bash -e
### Copyright 1999-2016. Parallels IP Holdings GmbH.

plesk bin extension --exec security-advisor run-letsencrypt.php $@ 1>/dev/null 2>&1 &
