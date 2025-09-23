FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    zip \
    curl \
    git \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    cron \
    wget \
    gnupg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        intl \
        mbstring \
        bcmath

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# install node + npm
RUN apt-get update && apt-get install -y nodejs npm

# Set working directory
WORKDIR /var/www

# Copy everything
COPY . .

# Set permissions
RUN chmod -R 775 /var/www/storage

EXPOSE 80

# Start Laravel dev server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
