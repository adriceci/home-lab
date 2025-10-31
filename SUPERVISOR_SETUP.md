# Configuración de Supervisor para Laravel Jobs

Esta guía explica cómo instalar y configurar Supervisor en el contenedor Docker para ejecutar automáticamente los jobs programados y los workers de cola de Laravel.

## 📋 Resumen Ejecutivo

Para configurar Supervisor y que ejecute automáticamente los jobs de Laravel, sigue estos pasos:

1. **Reconstruir el contenedor** con la nueva configuración:

    ```bash
    docker-compose down
    docker-compose build app
    docker-compose up -d
    ```

2. **Verificar que Supervisor está funcionando**:
    ```bash
    docker exec homelab-app supervisorctl status
    ```

¡Eso es todo! Supervisor iniciará automáticamente el scheduler y los workers cuando el contenedor se inicie.

## Requisitos

-   Docker y Docker Compose instalados
-   Acceso al servidor donde se ejecutan los contenedores

## Estructura de Archivos Creados

```
supervisor/
├── supervisord.conf          # Configuración principal de Supervisor
├── laravel-scheduler.conf     # Configuración del scheduler de Laravel
└── laravel-worker.conf        # Configuración del worker de colas
```

## Paso 1: Reconstruir el Contenedor

**Nota:** El Dockerfile ya ha sido actualizado para incluir Supervisor automáticamente. Solo necesitas reconstruir el contenedor.

Ejecuta los siguientes comandos en el servidor:

```bash
# Detener los contenedores actuales
docker-compose down

# Reconstruir la imagen del contenedor app
docker-compose build app

# Iniciar los contenedores
docker-compose up -d
```

## Paso 2: Verificar que Supervisor está Ejecutándose

```bash
# Verificar el estado de Supervisor dentro del contenedor
docker exec homelab-app supervisorctl status

# Deberías ver algo como:
# laravel-scheduler              RUNNING   pid 123, uptime 0:00:05
# laravel-worker:laravel-worker_00   RUNNING   pid 124, uptime 0:00:05
# laravel-worker:laravel-worker_01   RUNNING   pid 125, uptime 0:00:05
```

## Paso 3: Verificar los Logs

```bash
# Ver logs del scheduler
docker exec homelab-app tail -f storage/logs/scheduler.log

# Ver logs del worker
docker exec homelab-app tail -f storage/logs/worker.log

# Ver logs de Supervisor
docker exec homelab-app tail -f /var/log/supervisor/supervisord.log
```

## Comandos Útiles de Supervisor

### Dentro del contenedor:

```bash
# Entrar al contenedor
docker exec -it homelab-app bash

# Ver estado de los procesos
supervisorctl status

# Reiniciar un proceso específico
supervisorctl restart laravel-scheduler
supervisorctl restart laravel-worker:*

# Recargar configuración (sin detener procesos)
supervisorctl reread
supervisorctl update

# Detener todos los procesos
supervisorctl stop all

# Iniciar todos los procesos
supervisorctl start all
```

### Desde el servidor host:

```bash
# Ver estado
docker exec homelab-app supervisorctl status

# Reiniciar scheduler
docker exec homelab-app supervisorctl restart laravel-scheduler

# Reiniciar workers
docker exec homelab-app supervisorctl restart laravel-worker:*
```

## Jobs Configurados

Actualmente, la aplicación tiene el siguiente job programado:

-   **CleanupQuarantineJob**: Se ejecuta diariamente a las 7:00 AM para limpiar archivos antiguos de cuarentena (más de 10 días)

Este job está definido en `bootstrap/app.php` y se ejecuta automáticamente a través del scheduler.

## Solución de Problemas

### Supervisor no inicia

1. Verifica los logs:

    ```bash
    docker exec homelab-app cat /var/log/supervisor/supervisord.log
    ```

2. Verifica que los archivos de configuración estén en su lugar:
    ```bash
    docker exec homelab-app ls -la /etc/supervisor/conf.d/
    ```

### Los jobs no se ejecutan

1. Verifica que el scheduler esté corriendo:

    ```bash
    docker exec homelab-app supervisorctl status laravel-scheduler
    ```

2. Verifica los logs del scheduler:

    ```bash
    docker exec homelab-app tail -n 50 storage/logs/scheduler.log
    ```

3. Ejecuta manualmente el scheduler para probar:
    ```bash
    docker exec homelab-app php artisan schedule:run
    ```

### Los workers no procesan jobs

1. Verifica que los workers estén corriendo:

    ```bash
    docker exec homelab-app supervisorctl status laravel-worker:*
    ```

2. Verifica los logs del worker:

    ```bash
    docker exec homelab-app tail -n 50 storage/logs/worker.log
    ```

3. Verifica que hay jobs en la cola:
    ```bash
    docker exec homelab-app php artisan queue:work --once
    ```

## Configuración Avanzada

### Ajustar número de workers

Edita `supervisor/laravel-worker.conf` y cambia:

```
numprocs=2
```

por el número de workers que desees. Luego reconstruye el contenedor.

### Cambiar la frecuencia de verificación del scheduler

El scheduler de Laravel verifica cada minuto si hay tareas programadas. Esto se maneja automáticamente por `schedule:work`.

### Agregar más jobs programados

Edita `bootstrap/app.php` y agrega más tareas en la función `withSchedule`:

```php
->withSchedule(function (Schedule $schedule): void {
    $schedule->job(new CleanupQuarantineJob(10))->dailyAt('07:00');
    // Agregar más tareas aquí
    $schedule->command('tu:comando')->hourly();
})
```

## Monitoreo

Para monitorear el estado de Supervisor continuamente, puedes usar:

```bash
watch -n 2 'docker exec homelab-app supervisorctl status'
```

Esto mostrará el estado cada 2 segundos.
