#!/bin/bash
cp /var/www/html/conf/php/* /usr/local/etc/php/conf.d/
chmod 644 /usr/local/etc/php/conf.d/*
chown root:root /usr/local/etc/php/conf.d/*
chown -Rf nginx.nginx /var/www/flightairmap
chmod 777 -R /var/www/flightairmap/install/tmp
chmod 777 -R /var/www/flightairmap/data
chmod 777 /var/www/flightairmap/require/settings.php
if grep -q '$globalInstalled = FALSE' /var/www/flightairmap/require/settings.php
then
	sed -i 's:$globalDBhost = '"'localhost'"':$globalDBhost = '"'$MYSQL_HOST'"':g' /var/www/flightairmap/require/settings.php
	sed -i 's:$globalDBuser = '"''"':$globalDBuser = '"'$MYSQL_USER'"':g' /var/www/flightairmap/require/settings.php
	sed -i 's:$globalDBpass = '"''"':$globalDBpass = '"'$MYSQL_PASSWORD'"':g' /var/www/flightairmap/require/settings.php
	sed -i 's:$globalDBname = '"''"':$globalDBname = '"'$MYSQL_DATABASE'"':g' /var/www/flightairmap/require/settings.php
fi