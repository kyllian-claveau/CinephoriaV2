# Étape 1 : Construire l'application
FROM php:8.3-cli AS builder
WORKDIR /app

# Installer des dépendances système
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip curl \
    libmariadb-dev \
    libssl3 libcurl4-openssl-dev pkg-config \
    libxml2-dev && \
    docker-php-ext-install pdo pdo_mysql zip xml

# Installer l'extension MongoDB via PECL
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer global config --no-plugins allow-plugins.symfony/flex true \
    && composer global require symfony/flex \
    && composer clear-cache

# Cloner le repository GitHub
RUN git clone https://github.com/kyllian-claveau/CinephoriaV2.git /app

# Copier le fichier .env directement dans /app
COPY .env /app/.env
RUN chmod 644 /app/.env

# Installer les dépendances PHP via Composer
WORKDIR /app
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=ext-mongodb
RUN chown -R www-data:www-data /var/www/cinephoria/vendor

# Étape 2 : Image de production
FROM php:8.3-apache
WORKDIR /var/www/cinephoria

# Installer les extensions MySQL
RUN apt-get update && apt-get install -y \
    libmariadb-dev \
    libssl3 \
    libxml2-dev && \
    docker-php-ext-install pdo pdo_mysql xml

# Installer l'extension MongoDB
RUN apt-get update && apt-get install -y libssl3 libcurl4-openssl-dev pkg-config && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Configuration Apache
RUN a2enmod rewrite
RUN sed -i 's|/var/www/cinephoria|/var/www/cinephoria/public|g' /etc/apache2/sites-available/000-default.conf

# Copier l'application
COPY --from=builder /app /var/www/cinephoria

# Configuration .htaccess
RUN echo '<IfModule mod_rewrite.c>\n\
    Options -MultiViews\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteRule ^ index.php [QSA,L]\n\
</IfModule>' > /var/www/cinephoria/public/.htaccess

# Permissions
RUN chmod -R 775 /var/www/cinephoria /var/www/cinephoria/var /var/www/cinephoria/public \
    && chown -R www-data:www-data /var/www/cinephoria

EXPOSE 80
CMD ["apache2-foreground"]
