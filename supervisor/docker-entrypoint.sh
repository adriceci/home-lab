#!/bin/bash

# Asegurar que los directorios existen
mkdir -p /var/log/supervisor /var/run /etc/supervisor/conf.d

# Limpiar socket stale si existe
rm -f /var/run/supervisor.sock /var/run/supervisord.pid

# Iniciar PHP-FPM en segundo plano
php-fpm -D || echo "Warning: PHP-FPM ya estaba corriendo"

# Esperar un momento para que PHP-FPM inicie
sleep 2

# Verificar si los archivos de configuración existen
if [ ! -f /etc/supervisor/supervisord.conf ]; then
    echo "Error: /etc/supervisor/supervisord.conf no existe. El contenedor necesita ser reconstruido."
    echo "Ejecuta: docker-compose build app"
    # Mantener el contenedor vivo ejecutando PHP-FPM en primer plano
    exec php-fpm -F
fi

# Iniciar Supervisor en primer plano (esto mantiene el contenedor vivo)
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf -n

