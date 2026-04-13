FROM --platform=linux/amd64 php:5.6-apache

# Set timezone — required in PHP 5.3+; missing it fires a warning before
# headers are sent, which breaks Kohana's output entirely.
RUN echo "date.timezone=UTC" > /usr/local/etc/php/conf.d/timezone.ini

# mysql extension — available in PHP 5.6, needs explicit activation
RUN docker-php-ext-install mysql mysqli

# Enable mod_rewrite for Kohana URL routing
RUN a2enmod rewrite

# Allow .htaccess overrides in the document root
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
