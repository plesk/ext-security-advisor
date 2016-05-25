#!/bin/sh
### Copyright 1999-2016. Parallels IP Holdings GmbH. All Rights Reserved.

[ -f /usr/local/psa/admin/conf/panel.ini ] && exit 0

cat > /usr/local/psa/admin/conf/panel.ini <<EOF
[debug]
enabled = on

[log]
priority = 7

[cli]
gate.allowedIPs =
EOF
