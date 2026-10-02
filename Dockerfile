FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && (a2dismod mpm_event mpm_worker 2>/dev/null || true) \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork rewrite headers \
    && echo 'PassEnv DATABASE_URL MYSQL_URL MYSQL_PRIVATE_URL MYSQLHOST MYSQLPORT MYSQLUSER MYSQLPASSWORD MYSQLDATABASE MYSQL_DATABASE DB_HOST DB_PORT DB_USER DB_PASS DB_NAME PORT' > /etc/apache2/conf-available/railway-env.conf \
    && a2enconf railway-env

WORKDIR /var/www/html
COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080
CMD ["/bin/sh", "-c", "port=${PORT:-8080}; sed -i \"s/^Listen 80$/Listen ${port}/\" /etc/apache2/ports.conf; sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${port}>/\" /etc/apache2/sites-available/000-default.conf; exec apache2-foreground"]
