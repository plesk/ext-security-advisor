#!/bin/bash -e
### Copyright 1999-2016. Parallels IP Holdings GmbH.

plesk bin extension --exec letsencrypt cli.php $@
