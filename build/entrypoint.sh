#!/bin/sh
#
# Entrypoint Script for the container

/usr/bin/umask 0002

/usr/sbin/php-fpm -D
echo "127.0.0.1 fpm-status.localhost" >> /etc/hosts

/usr/sbin/nginx -g "daemon off;"
