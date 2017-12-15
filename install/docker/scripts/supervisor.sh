#!/bin/bash
mkdir -p /etc/supervisor/conf.d/
ln -s /var/www/html/conf/supervisor/flightairmap.conf /etc/supervisor/conf.d/flightairmap.conf
supervisorctl start flightairmap
