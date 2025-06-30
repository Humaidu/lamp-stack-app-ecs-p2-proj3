# --- Stage 1: build stage (just a temporary container to hold app source) ---
FROM php:8.2-cli AS build

# Copy your app source to the build stage
COPY ./php-app/ /app/

# --- Stage 2: final image ---
FROM php:8.2-apache

# Install only needed PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy app files from build stage
COPY --from=build /app/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port
EXPOSE 80
