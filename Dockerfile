FROM php:8.4-apache

RUN a2enmod rewrite && rm -rf /var/www/html

RUN apt-get update && apt-get install -y unzip && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install ftp pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/public|g' \
    /etc/apache2/sites-available/000-default.conf

RUN echo '<Directory /var/www>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
