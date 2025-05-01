#!/bin/bash

FILE=/var/www/html/.htaccess
if [ ! -f "$FILE" ]; then
	cp -r /usr/src/facturascripts/* /var/www/html/; \
	chmod -R o+w /var/www/html
fi

apache2-foreground