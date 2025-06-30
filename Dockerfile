FROM php:8.1-apache

# Install SQLite and PDO SQLite
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set permissions for SQLite database
RUN chmod 664 /var/www/html/db/ecobuddy.sqlite
RUN chmod 775 /var/www/html/db/
RUN chown -R www-data:www-data /var/www/html/

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]