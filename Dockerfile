# Utilise l'image PHP 8.2 avec Apache (adaptée à votre version)
FROM php:8.3-apache

# Installe les dépendances nécessaires (MySQL/PDO pour la BDD et Git)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    git \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql opcache \
    && a2enmod rewrite

# Installe Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définit le répertoire de travail
WORKDIR /var/www/html

# 4. Configuration d'Apache pour utiliser le dossier 'public' de Symfony
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf