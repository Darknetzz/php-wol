FROM php:8.5-fpm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        iputils-ping \
        libcap2-bin \
        libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite sockets \
    && setcap cap_net_raw+ep /bin/ping \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/www/html/data /run/php /var/log/supervisor \
    && chown -R www-data:www-data /var/www/html

COPY docker/nginx.conf /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/www.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

COPY --chown=www-data:www-data public /var/www/html/public
COPY --chown=www-data:www-data src /var/www/html/src

WORKDIR /var/www/html

EXPOSE 9080

CMD ["/entrypoint.sh"]
