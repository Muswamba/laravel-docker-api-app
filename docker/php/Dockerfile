# Start from a base PHP image with FPM (FastCGI Process Manager)
FROM php:8.4-fpm

# Install system dependencies and PHP extensions required by Laravel
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    # Clean up APT files to reduce image size
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd sockets

# Set the working directory inside the container
WORKDIR /var/www/public

# The remaining files will be mounted from your host machine (Windows)
