#!/bin/sh
### Copyright 1999-2016. Parallels IP Holdings GmbH. All Rights Reserved.

export DEBIAN_FRONTEND=noninteractive

command -v vim >/dev/null && continue || { apt-get update; apt-get install -y vim; }

DIR=/vagrant/vagrant
HOME=/home/vagrant
for FILE in bashrc vimrc; do
    [ -f $HOME/.$FILE -a ! -h $HOME/.$FILE ] && mv $HOME/.$FILE $HOME/.$FILE.orig
    [ ! -f $HOME/.$FILE ] && ln -vsf $DIR/$FILE $HOME/.$FILE
done
echo 'cd /vagrant' >> $HOME/.bash_extra
