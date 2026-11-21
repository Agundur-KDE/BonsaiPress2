# PHP security fixes from 19 November 2026; keep the supported 8.4 series.
FROM php:8.4.28-apache

RUN a2enmod rewrite && rm -rf /var/www/html

# Refresh inherited packages too: installing tools alone leaves base-image CVEs.
RUN apt-get update && apt-get upgrade -y && \
    apt-get install -y unzip git && rm -rf /var/lib/apt/lists/*
RUN git config --system --add safe.directory '*'

RUN docker-php-ext-install ftp pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/public|g' \
    /etc/apache2/sites-available/000-default.conf

RUN echo '<Directory /var/www>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Bake CMS core + dependencies into image — users don't need a git clone
COPY cms/     /var/www/cms/
COPY vendor/  /var/www/vendor/
COPY bin/     /var/www/bin/
COPY public/  /var/www/public/
COPY docker/cms.conf     /etc/apache2/sites-available/cms.conf
COPY docker/preview.conf /etc/apache2/sites-available/preview.conf
RUN a2ensite cms.conf && a2dissite 000-default.conf

COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]
