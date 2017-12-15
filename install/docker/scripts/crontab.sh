#!/bin/bash
crontab /var/www/html/conf/cron/crontab
if ! [ -f "/etc/supervisor/conf.d/crond.conf" ]; then
	mkdir -p /etc/supervisor/conf.d
	ln -s /var/www/html/conf/supervisor/cron.conf /etc/supervisor/conf.d/crond.conf
	supervisorctl start crond
fi