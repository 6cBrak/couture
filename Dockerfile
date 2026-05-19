FROM php:8.2-apache

# Extensions PHP nécessaires
RUN apt-get update --fix-missing && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev libonig-dev zip unzip cron \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Activer mod_rewrite
RUN a2enmod rewrite

# Config Apache : AllowOverride pour .htaccess
RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# Copier le code
WORKDIR /var/www/html
COPY . .

# Permissions sur les uploads
RUN chown -R www-data:www-data /var/www/html/uploads \
    && chmod -R 775 /var/www/html/uploads

# Cron : bilan journalier à 20h00
RUN echo "0 20 * * * www-data /usr/local/bin/php /var/www/html/cron/bilan_journalier.php >> /var/log/bilan_journalier.log 2>&1" \
    > /etc/cron.d/bilan_journalier \
    && chmod 0644 /etc/cron.d/bilan_journalier

# Script de démarrage : lance cron + apache
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
CMD ["docker-entrypoint.sh"]
