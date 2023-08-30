# --------------------------------------------
#       STAGE 1.1: Build JS with node
# --------------------------------------------

FROM node:16-alpine as node
WORKDIR /app

# install dependencies (only copy package lock here to use docker caching)
COPY ["package.json", "package-lock.json", "./"] 
RUN npm install

# copy project data
COPY ["vite.config.js", "./"]
COPY ["./resources/js/", "./resources/js/"]
COPY ["./resources/css/", "./resources/css/"]

# build project
RUN npm run build

# --------------------------------------------
#    STAGE 1.2: Setup PHP and Dependencies
# --------------------------------------------

FROM php:8.1-cli-alpine as php
LABEL maintainer="FSR5 FH-Aachen"
WORKDIR /var/www/html

# install php extensions
RUN apk add libpq-dev
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install bcmath sockets pdo_mysql pdo pdo_pgsql pgsql pcntl
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS && pecl install redis && docker-php-ext-enable redis.so

# install composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# copy relevant project data
COPY ["composer.json", "composer.lock", "artisan", "./"]
COPY ["./app", "./app"]
COPY ["./bootstrap", "./bootstrap"]
COPY ["./config", "./config"]
COPY ["./database", "./database"]
COPY ["./lang", "./lang"]
COPY ["./public", "./public"]
COPY ["./resources/css", "./resources/css"]
COPY ["./resources/views", "./resources/views"]
COPY ["./routes", "./routes"]
COPY ["./storage", "./storage"]

# install dependencies
RUN composer install

# get data from previous build
COPY --from=node ["/app/public/build/", "./public/build/"]

# set permissions
RUN chmod -R 777 ./storage

# install and configure roadrunner
COPY --from=spiralscout/roadrunner:latest /usr/bin/rr /usr/bin/rr
RUN php artisan octane:install --server=roadrunner

ENV ROADRUNNER_MAX_REQUESTS=512
ENV ROADRUNNER_WORKERS="auto"

EXPOSE 8000

CMD php artisan octane:start --server="roadrunner" --host="0.0.0.0" --workers=${ROADRUNNER_WORKERS} --max-requests=${ROADRUNNER_MAX_REQUESTS}

HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 CMD php artisan octane:status --server="roadrunner"
