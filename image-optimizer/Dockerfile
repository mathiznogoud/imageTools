FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nodejs \
    npm

# Install image optimization tools
RUN apt-get install -y \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    webp \
    libavif-bin

# Install SVGO globally using npm
RUN npm install -g svgo

# Install PHP extensions
RUN docker-php-ext-install exif pcntl bcmath gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /app

# Copy source files
COPY src/ .

# Install dependencies
RUN composer install

# Make the script executable
RUN chmod +x optimize.php

# Set the entry point
ENTRYPOINT ["php", "optimize.php"]
