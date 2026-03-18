# Use PHP 8.2 FPM
FROM php:8.2-fpm

WORKDIR /var/www

# Timezone & environment
ENV TZ=America/Los_Angeles
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
 && echo $TZ > /etc/timezone

# 1) System deps: GD, Imagick, MySQL & SQLite clients + dev headers
RUN apt-get update && apt-get install -y --no-install-recommends \
      build-essential \
      autoconf \
      pkg-config \
      imagemagick \
      libmagickcore-dev \
      libmagickwand-dev \
      libpng-dev \
      libjpeg-dev \
      libfreetype6-dev \
      zlib1g-dev \
      libzip-dev \
      libsqlite3-dev \
      sqlite3 \
      default-mysql-client \
      git \
      unzip \
 && rm -rf /var/lib/apt/lists/*

# 2) PECL / PHP extensions
RUN pecl install imagick-3.7.0 && docker-php-ext-enable imagick

RUN docker-php-ext-configure gd \
      --with-freetype=/usr/include/freetype2 \
      --with-jpeg=/usr/include \
 && docker-php-ext-install -j"$(nproc)" \
      gd \
      pdo_mysql \
      pdo_sqlite \
      zip

# 3) Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
      --install-dir=/usr/local/bin --filename=composer

# 4) Provision SQLite file
RUN mkdir -p database \
 && touch database/database.sqlite \
 && chown -R www-data:www-data database

# 5) Copy code & install PHP deps
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
 && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
