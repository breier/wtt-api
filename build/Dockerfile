FROM redhat/ubi9:9.6

LABEL maintainer="Andre Breier <andre@breier.ie>" version="1.0"

RUN dnf install -y https://dl.fedoraproject.org/pub/epel/epel-release-latest-9.noarch.rpm
RUN dnf install -y https://rpms.remirepo.net/enterprise/remi-release-9.rpm
RUN dnf module reset -y php && dnf module install -y php:remi-8.4 \
    && dnf install -y php-gd php-mysqlnd php-pecl-zip php-pecl-redis6 composer nginx \
    && dnf upgrade -y

ADD entrypoint.sh /usr/sbin/entrypoint.sh
ADD fpm-status.conf /etc/nginx/conf.d/fpm-status.conf
ADD vhost.conf /etc/nginx/conf.d/vhost.conf

RUN useradd -s /bin/bash -d /home/wtt_user -g users -G nginx -m wtt_user \
    && ln -sf /usr/share/zoneinfo/Europe/Dublin /etc/localtime \
    && ln -sf /proc/1/fd/1 /var/log/nginx/access.log \
    && ln -sf /proc/1/fd/2 /var/log/nginx/error.log \
    && ln -sf /proc/1/fd/1 /var/log/php-fpm/error.log \
    && ln -sf /proc/1/fd/2 /var/log/php-fpm/www-error.log \
    && sed -i 's/^variables_order = "G/variables_order = "EG/' /etc/php.ini \
    && sed -i 's/^expose_php = On/expose_php = Off/' /etc/php.ini \
    && sed -i 's/^;clear_env/clear_env/' /etc/php-fpm.d/www.conf \
    && sed -i 's/^;catch_workers_output/catch_workers_output/' /etc/php-fpm.d/www.conf \
    && sed -i 's/^;pm.status_path = \//pm.status_path = \/fpm-/' /etc/php-fpm.d/www.conf \
    && sed -i 's/^php_value\[soap.wsdl_cache_dir\]/;php_value\[soap.wsdl_cache_dir\]/' /etc/php-fpm.d/www.conf \
    && sed -i 's/^;listen.owner = nobody/listen.owner = nginx/' /etc/php-fpm.d/www.conf \
    && sed -i 's/^;listen.group = nobody/listen.group = nginx/' /etc/php-fpm.d/www.conf \
    && sed -i 's/^;listen.mode = 0660/listen.mode = 0660/' /etc/php-fpm.d/www.conf \
    && sed -i 's/^listen.acl_users =/;listen.acl_users =/' /etc/php-fpm.d/www.conf \
    && sed -i 's/apache/nginx/' /etc/php-fpm.d/www.conf \
    && echo 'session.save_handler = redis' >> /etc/php.d/50-redis.ini \
    && echo 'session.save_path = /var/lib/php/sessions' >> /etc/php.d/50-redis.ini \
    && chmod +x /usr/sbin/entrypoint.sh \
    && mkdir -p /run/php-fpm

ENTRYPOINT [ "/usr/sbin/entrypoint.sh" ]

HEALTHCHECK --interval=5s --timeout=1s --start-period=2s --retries=2 \
    CMD curl -s http://fpm-status.localhost/fpm-status | grep 'total processes:' || exit 1

EXPOSE 80
