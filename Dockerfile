FROM php:8.0-apache

# Install dependencies
RUN apt-get update && \
	apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev libpq-dev libzip-dev unzip libxml2-dev && \
	apt-get clean && \
	a2enmod rewrite && \
	docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ && \
	docker-php-ext-install bcmath gd mysqli pdo pdo_mysql pgsql zip soap

ENV FS_VERSION 2024.92

# Download FacturaScripts
ADD https://facturascripts.com/DownloadBuild/1/${FS_VERSION} /tmp/facturascripts.zip

# Unzip
RUN unzip -q /tmp/facturascripts.zip -d /usr/src/; \
	rm -rf /tmp/facturascripts.zip

VOLUME /var/www/html

COPY facturascripts.sh /usr/local/bin/facturascripts
RUN chmod +x /usr/local/bin/facturascripts
CMD ["facturascripts"]
