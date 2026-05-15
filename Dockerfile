FROM php:8.2-apache

# Nainstaluj potřebné PHP rozšíření
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Nainstaluj curl rozšíření
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    libssl-dev \
    && docker-php-ext-install curl \
    && apt-get clean

# Povol mod_rewrite pro Apache
RUN a2enmod rewrite

# Nastav práva pro složky kam PHP zapisuje
RUN mkdir -p /var/www/html/data /var/www/html/games \
    && chown -R www-data:www-data /var/www/html/data /var/www/html/games

# Apache konfigurace - povol .htaccess
RUN echo '<Directory /var/www/html>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/bojovka.conf \
    && a2enconf bojovka
# Nainstaluj Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Nastav pracovní složku
WORKDIR /var/www/html