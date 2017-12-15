#!/bin/bash
if ! [ -f "$(php -r 'echo ini_get("extension_dir");')/gettext.so" ]; then
	apk add gettext-dev
	docker-php-ext-configure gettext
	docker-php-ext-install gettext
	supervisorctl restart php-fpm
fi
