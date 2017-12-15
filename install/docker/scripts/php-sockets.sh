#!/bin/bash
if ! [ -f "$(php -r 'echo ini_get("extension_dir");')/sockets.so" ]; then
	docker-php-ext-configure sockets
	docker-php-ext-install sockets
	supervisorctl restart php-fpm
fi
