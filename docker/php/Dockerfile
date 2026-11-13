FROM php:8.3-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libonig-dev libpq-dev librdkafka-dev libxml2-dev libzip-dev libcurl4-openssl-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring pcntl pdo pdo_pgsql pgsql zip \
    && pecl install redis rdkafka \
    && docker-php-ext-enable redis rdkafka \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd --gid "${GID}" app \
    && useradd --uid "${UID}" --gid app --create-home app

WORKDIR /var/www/html

USER app
