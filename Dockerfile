# Apache + PHP 8.3

FROM php:8.3-apache

# Paquets système & extensions PHP utiles à Symfony

RUN apt-get update && apt-get install -y \

    libzip-dev \

    libicu-dev \

    git \

    unzip \
&& docker-php-ext-install pdo pdo_mysql intl zip opcache \
&& a2enmod rewrite

# Composer (depuis l'image officielle)

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Répertoire de travail

WORKDIR /var/www/html

# DocumentRoot sur /public

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Met à jour le vhost par défaut

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf

# Autoriser .htaccess (indispensable pour Symfony)

RUN printf '<Directory "${APACHE_DOCUMENT_ROOT}">\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
> /etc/apache2/conf-available/symfony-override.conf \
&& a2enconf symfony-override

 