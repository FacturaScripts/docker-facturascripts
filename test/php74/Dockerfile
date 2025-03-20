FROM php:7.4-apache

# Install dependencies
RUN apt-get update && \
	apt-get install -y libfreetype6-dev libjpeg62-turbo-dev libpng-dev libpq-dev libzip-dev unzip libxml2-dev && \
	apt-get clean && \
	a2enmod rewrite

# Install GD
RUN docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
RUN docker-php-ext-install gd

# Install other extensions one by one
RUN docker-php-ext-install bcmath
RUN docker-php-ext-install mysqli pdo pdo_mysql pgsql zip
RUN docker-php-ext-install soap

ENV FS_VERSION 2024.94

# Download FacturaScripts
ADD https://facturascripts.com/DownloadBuild/1/${FS_VERSION} /tmp/facturascripts.zip

# Unzip
RUN unzip -q /tmp/facturascripts.zip -d /usr/src/; \
	rm -rf /tmp/facturascripts.zip

VOLUME /var/www/html

COPY facturascripts.sh /usr/local/bin/facturascripts
RUN chmod +x /usr/local/bin/facturascripts
CMD ["../../facturascripts"]
