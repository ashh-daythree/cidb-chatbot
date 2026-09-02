FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_INI_SCAN_DIR=":/usr/local/etc/php-extra"

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

# Uploaded IC photos routinely exceed PHP's stock 2M upload limit.
RUN mkdir -p /usr/local/etc/php-extra \
    && printf 'upload_max_filesize=12M\npost_max_size=16M\nmemory_limit=256M\n' \
       > /usr/local/etc/php-extra/zz-cidb.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY ocr/requirements.txt /app/ocr/requirements.txt
RUN python -m venv /opt/ocr-venv \
    && /opt/ocr-venv/bin/pip install --no-cache-dir --upgrade pip \
    && /opt/ocr-venv/bin/pip install --no-cache-dir -r /app/ocr/requirements.txt

COPY . /app

# Must run after COPY: the autoload classmap is built by scanning backend/.
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

COPY docker/supervisord.conf /etc/supervisor/conf.d/cidb.conf

RUN mkdir -p /app/backend/logs /app/backend/storage/documents /app/backend/uploads

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=10s --start-period=300s --retries=3 \
    CMD curl --fail http://127.0.0.1:8080/health && curl --fail http://127.0.0.1:8002/health || exit 1

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
