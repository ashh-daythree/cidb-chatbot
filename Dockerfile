FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        libgl1 \
        libglib2.0-0 \
        libgomp1 \
        php-cli \
        php-curl \
        php-mbstring \
        php-pgsql \
        php-xml \
        php-zip \
        supervisor \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

COPY ocr/requirements.txt /app/ocr/requirements.txt
RUN python -m venv /opt/ocr-venv \
    && /opt/ocr-venv/bin/pip install --no-cache-dir --upgrade pip \
    && /opt/ocr-venv/bin/pip install --no-cache-dir -r /app/ocr/requirements.txt

COPY . /app
COPY docker/supervisord.conf /etc/supervisor/conf.d/cidb.conf

RUN mkdir -p /app/backend/logs /app/backend/storage/documents /app/backend/uploads

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=10s --start-period=300s --retries=3 \
    CMD curl --fail http://127.0.0.1:8080/health && curl --fail http://127.0.0.1:8002/health || exit 1

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
