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
    libmagickwand-dev \
    imagemagick \
    ghostscript \
    nodejs \
    npm \
    poppler-utils \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        zip \
        intl \
        mbstring \
        bcmath \
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy everything
COPY . .

# Set permissions
RUN chmod -R 775 /var/www/storage

EXPOSE 80

# Start Laravel dev server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
