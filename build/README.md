# Redhat Universal Base Image 9 - PHP 8.4
PHP 8.4 is the latest stable version as of writing this file.

## Extra Packages
 * **php-pecl-zip:** is an optional dependency for composer;
 * **git:** may also be used by composer for custom sources.

## Unprivileged User
 * **wtt_user:** is part of the nginx group for this image. Good to run `composer`.

## Permissions
 * **umask 0002:** for php-fpm and nginx services so the folders can be operated by the unprivileged user.

## Timezone
 * **Europe/Dublin:** is the default.

## PHP Settings
 * **variables_order:** Default is "GPCS", here it's changed to "**E**GPCS" to support environment variables;
 * **clear_env:** php-fpm Default is "yes", here it's changed to "no".

## Health Check
 * **fpm-status.conf:** adds a local only endpoint for fpm-status health check.
 * **nginx:** runs in foreground, so, if it fails, the container stops.

## Hosting Files
 * **/etc/nginx/conf.d/vhost.conf:** To define the main host for the app.
