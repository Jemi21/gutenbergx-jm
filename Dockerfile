# Use PHP 8.1 with Apache
FROM php:8.1-apache

# Install system dependencies
RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev git zip unzip \
 && docker-php-ext-configure pdo_pgsql --with-pgsql=/usr/local/pgsql \
 && docker-php-ext-install pdo_pgsql pgsql \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy composer from composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy app source code
COPY . /var/www/html

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --no-ansi --no-dev \
 && chown -R www-data:www-data /var/www/html

# Set Apache document root to /public
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Update Apache configuration
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && sed -ri 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf \
 && echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Expose the port
EXPOSE 80

# Start Apache with dynamic Render port
CMD ["bash", "-c", "sed -i 's/80/${PORT}/g' /etc/apache2/ports.conf && sed -i 's/*:80/*:${PORT}/g' /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
