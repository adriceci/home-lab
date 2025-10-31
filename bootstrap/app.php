<?php

use AdriCeci\AuditCenter\Http\Middleware\AuditLogMiddleware;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Jobs\CleanupQuarantineJob;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            EnsureFrontendRequestsAreStateful::class,
        ]);

        // Register audit logging middleware
        $middleware->api(append: [
            AuditLogMiddleware::class,
        ]);

        // Register admin middleware alias
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Cleanup quarantine files daily at 7:00 AM
        $schedule->job(new CleanupQuarantineJob(10))->dailyAt('07:00');
    })->create();
