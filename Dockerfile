# --------------------------------------------
#       STAGE 1.1: Build JS with node
# --------------------------------------------

FROM node:20-alpine AS node
WORKDIR /app

# install dependencies (only copy package lock here to use docker caching)
COPY ["package.json", "package-lock.json", "./"] 
RUN npm install

# copy project data
COPY ["vite.config.js", "./"]
COPY ["./resources/js/", "./resources/js/"]
COPY ["./resources/css/", "./resources/css/"]
COPY ["./resources/views/", "./resources/view/"]
COPY ["postcss.config.cjs", "./"]
COPY ["tailwind.config.cjs", "./"]
COPY ["./database", "./database"]

# build project
RUN npm run build

# --------------------------------------------
#    STAGE 1.2: Setup PHP and Dependencies
# --------------------------------------------

FROM phpswoole/swoole:php8.3-alpine AS swoole
LABEL maintainer="FSR5 FH-Aachen"

# use workdir from swoole container
WORKDIR /app

# install php extensions
RUN apk add libpq-dev linux-headers
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql
RUN docker-php-ext-install bcmath pdo_mysql pdo pdo_pgsql pgsql pcntl sockets
# RUN apk add --no-cache pcre-dev $PHPIZE_DEPS && pecl install redis && docker-php-ext-enable redis.so

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
COPY --from=node ["/app/public/css/", "./public/css/"]

# fix storage folder
RUN mkdir -p /app/storage/logs
RUN chmod -R 777 ./storage

# both are the default values
ENV OCTANE_MAX_REQUESTS=500
ENV OCTANE_WORKERS="auto"

EXPOSE 8000

CMD php artisan octane:start --server=swoole --host="0.0.0.0" --workers=${OCTANE_WORKERS} --max-requests=${OCTANE_MAX_REQUESTS}

HEALTHCHECK --interval=30s --timeout=30s --start-period=5s --retries=3 CMD php artisan octane:status
