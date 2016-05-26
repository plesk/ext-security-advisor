#!/bin/bash

plesk bin extension --exec dgri get-results.php $@
if [ $? -ne 0 ]; then
	plesk bin extension --exec security-wizard get-dgri-results.php $@
fi