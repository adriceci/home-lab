#!/bin/bash
set -e

# Iniciar PHP-FPM en segundo plano
php-fpm -D

# Iniciar Supervisor en primer plano (esto mantiene el contenedor vivo)
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf

