# Dockerfile
FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libmagickwand-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    libavif-dev \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN pecl install imagick \
    && docker-php-ext-enable imagick \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Set working directory
WORKDIR /app

# Copy the converter script
COPY convert.php /app/convert.php

# Make the script executable
RUN chmod +x /app/convert.php

# Create directories for input and output
RUN mkdir -p /app/input /app/output

# Set the entrypoint
ENTRYPOINT ["php", "/app/convert.php"]
