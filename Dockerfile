FROM dunglas/frankenphp

ENV SERVER_NAME=cinephoria.ovh
ENV APP_RUNTIME=Runtime\\FrankenPhpSymfony\\Runtime
ENV APP_ENV=prod
ENV FRANKENPHP_CONFIG="worker .public/index.php"

COPY . /app/
