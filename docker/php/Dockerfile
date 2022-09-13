FROM php:7.4-fpm

RUN apt-get update && apt-get install -y git-core zlib1g-dev libzip-dev zip unzip libzmq5 libzmq5-dev wget openssl libssl-dev curl libcurl4-openssl-dev libpcre3-dev build-essential
RUN docker-php-ext-install zip

RUN pecl install xdebug

RUN echo 'xdebug.file_link_format="phpstorm://open?url=file://%%f&line=%%l"' >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.mode=develop" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.max_nesting_level=10000" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.remote_handler=dbgp" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && \
        echo "xdebug.remote_mode=req" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

RUN git clone --depth=1 https://github.com/zeromq/php-zmq.git /tmp/php-zmq && \
    cd /tmp/php-zmq && \
    phpize && ./configure && \
    make && make install && \
    echo "extension=zmq.so" > /usr/local/etc/php/conf.d/zmq.ini

ARG SWOOLE_VERSION="4.10.0"
RUN cd /tmp && wget https://github.com/openswoole/swoole-src/archive/refs/tags/v${SWOOLE_VERSION}.tar.gz && \
    tar zxvf v${SWOOLE_VERSION}.tar.gz && \
    cd swoole-src-${SWOOLE_VERSION}  && \
    phpize  && ./configure --enable-openssl --enable-debug && \
    make && make install && \
    echo "extension=openswoole.so" > /usr/local/etc/php/conf.d/openswoole.ini

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php -r "if (hash_file('SHA384', 'composer-setup.php') === trim(file_get_contents('https://composer.github.io/installer.sig'))) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" && \
    php composer-setup.php && \
    mv composer.phar /usr/local/bin/composer && \
    unlink composer-setup.php

WORKDIR /srv/www
