#!/bin/bash
### Copyright 1999-2016. Parallels IP Holdings GmbH.

plesk bin extension --exec dgri get-results.php $@
if [ $? -ne 0 ]; then
	plesk bin extension --exec security-advisor get-dgri-results.php $@
fi
