FROM php:8.2-cli

WORKDIR /app

# Install dependencies
RUN apt-get update && apt-get install -y \
    libgmp-dev \
    && docker-php-ext-install gmp

# Copy application code
COPY . /app

# Set the command to run Reverb
CMD ["php", "/app/artisan", "reverb:start", "--debug"]