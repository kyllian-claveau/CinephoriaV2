# Étape 1 : Construire l'application
FROM php:8.3-cli AS builder
WORKDIR /app

# Installer des dépendances système
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev zip curl \
    libmysqlclient-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer global config --no-plugins allow-plugins.symfony/flex true \
    && composer global require symfony/flex \
    && composer clear-cache

# Cloner le repository GitHub
RUN git clone https://github.com/kyllian-claveau/CinephoriaV2.git /app

# Installer les dépendances PHP via Composer
WORKDIR /app
RUN composer install --no-dev --optimize-autoloader

# Étape 2 : Image de production
FROM php:8.3-apache
WORKDIR /var/www/html

# Installer les extensions MySQL
RUN apt-get update && apt-get install -y \
    libmysqlclient-dev \
    && docker-php-ext-install pdo pdo_mysql

# Configuration Apache
RUN a2enmod rewrite
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Copier l'application
COPY --from=builder /app /var/www/html

# Configuration .htaccess
RUN echo '<IfModule mod_rewrite.c>\n\
    Options -MultiViews\n\
    RewriteEngine On\n\
    RewriteCond %{REQUEST_FILENAME} !-f\n\
    RewriteRule ^ index.php [QSA,L]\n\
</IfModule>' > /var/www/html/public/.htaccess

# Permissions
RUN chmod -R 775 /var/www/html/var /var/www/html/public \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
CMD ["apache2-foreground"]
