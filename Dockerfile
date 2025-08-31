# PHP + Apache with Composer
FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libzip-dev \
    && docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Allow running Composer as root within the container
ENV COMPOSER_ALLOW_SUPERUSER=1

# Avoid git safe.directory ownership issue for the mounted repo
RUN git config --global --add safe.directory /var/www/html

# Set recommended PHP.ini settings
RUN { \
    echo "file_uploads = On"; \
    echo "memory_limit = 256M"; \
    echo "upload_max_filesize = 64M"; \
    echo "post_max_size = 64M"; \
    echo "max_execution_time = 60"; \
} > /usr/local/etc/php/conf.d/custom.ini

# Install PHP dependencies at runtime using:
# docker compose exec app composer install --no-interaction --prefer-dist --no-progress

# Set Apache DocumentRoot to project root
ENV APACHE_DOCUMENT_ROOT=/var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/default-ssl.conf

# Permissions for uploads directory if present
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads

EXPOSE 80

CMD ["apache2-foreground"]
