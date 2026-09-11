#!/bin/sh
set -e

HTTP_PORT="${HTTP_PORT:-9080}"
sed "s/__HTTP_PORT__/${HTTP_PORT}/g" /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

mkdir -p /var/www/html/data
chown -R www-data:www-data /var/www/html/data

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
