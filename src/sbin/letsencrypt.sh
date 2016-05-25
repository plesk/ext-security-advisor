#!/bin/bash -e

plesk bin extension --exec letsencrypt cli.php $@
