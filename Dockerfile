# ============================================================
#  Dockerfile — ShopNest (PHP 8.2 + Apache + Embedded MariaDB)
#  Self-contained: runs standalone with 1 command or with RDS
# ============================================================

FROM php:8.2-apache

# 1. Install dependencies, PHP extensions, and MariaDB server
RUN apt-get update && apt-get install -y \
    mariadb-server mariadb-client \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    zip unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pdo pdo_mysql gd zip opcache mysqli \
    && rm -rf /var/lib/apt/lists/*

# 2. Apache configuration
RUN a2enmod rewrite \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf

# 3. PHP custom configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/shopnest.ini

# 4. Copy application source code
WORKDIR /var/www/html
COPY . .

# 5. Setup Entrypoint and runtime directories
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p uploads logs /var/run/mysqld /var/lib/mysql \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/uploads /var/www/html/logs \
    && chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]