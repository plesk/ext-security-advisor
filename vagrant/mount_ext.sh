#!/bin/sh
### Copyright 1999-2016. Parallels IP Holdings GmbH.

EXT=$1
BASE=/usr/local/psa
SRC=/vagrant/src

create_symlink()
{
    TARGET=$1
    SOURCE=$2
    if [ ! -L $TARGET ]; then
        [ -d $TARGET ] && mv $TARGET $TARGET.old
        ln -s $SOURCE $TARGET
    fi
}

create_symlink $BASE/admin/htdocs/modules/$EXT $SRC/htdocs
create_symlink $BASE/admin/plib/modules/$EXT $SRC/plib

create_symlink $BASE/admin/sbin/modules/$EXT $SRC/sbin
[ -d $BASE/admin/bin/modules/$EXT ] || mkdir $BASE/admin/bin/modules/$EXT
for FILE in $SRC/sbin/*; do
    UTIL=$(basename $FILE)
    ln -s ../../../sbin/mod_wrapper $BASE/admin/bin/modules/$EXT/$UTIL
done

[ -d $BASE/var/modules/$EXT ] || mkdir $BASE/var/modules/$EXT
[ -f $BASE/admin/plib/modules/$EXT/meta.xml ] || cp $SRC/meta.xml $BASE/admin/plib/modules/$EXT/meta.xml
plesk bin extension --register $EXT
