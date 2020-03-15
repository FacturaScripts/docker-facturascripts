#!/bin/bash
cp -r /usr/src/facturascripts/* /var/www/html/; \
chmod -R o+w /var/www/html
exec "$@"