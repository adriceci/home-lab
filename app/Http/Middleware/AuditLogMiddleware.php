<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    /**
     * Routes that should be excluded from audit logging
     */
    protected $excludedRoutes = [
        'api/audit-logs*', // Exclude audit log endpoints to avoid recursion
        'api/user', // Exclude user endpoint to avoid spam
        'api/login', // Exclude login - handled by AuthController
        'api/register', // Exclude register - handled by AuthController
        'api/logout', // Exclude logout - handled by AuthController
    ];

    /**
     * HTTP methods that should be logged
     */
    protected $loggedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log if the request should be audited
        if ($this->shouldLog($request)) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    /**
     * Handle a job failure.
     */
    public function terminate($request, $response): void
    {
        // Handle failed requests (like failed logins)
        if ($this->shouldLogFailedRequest($request, $response)) {
            $this->logFailedRequest($request, $response);
        }
    }

    /**
     * Determine if the request should be logged
     */
    protected function shouldLog(Request $request): bool
    {
        // Don't log if it's not an API route
        if (!$request->is('api/*')) {
            return false;
        }

        // Don't log excluded routes
        foreach ($this->excludedRoutes as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        // Only log specific HTTP methods
        if (!in_array($request->method(), $this->loggedMethods)) {
            return false;
        }

        // Only log if user is authenticated (middleware runs after auth middleware)
        if (!Auth::check()) {
            return false;
        }

        return true;
    }

    /**
     * Log the request and response
     */
    protected function logRequest(Request $request, Response $response): void
    {
        $action = $this->determineAction($request);
        $modelType = $this->determineModelType($request);
        $modelId = $this->extractModelId($request);
        $oldValues = $this->getOldValues($request, $modelType, $modelId);
        $newValues = $this->getNewValues($request, $response);
        $description = $this->generateDescription($request, $action, $modelType);

        try {
            AuditLog::log(
                action: $action,
                modelType: $modelType,
                modelId: $modelId,
                oldValues: $oldValues,
                newValues: $newValues,
                description: $description,
                userId: Auth::id(),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                url: $request->url(),
                method: $request->method()
            );
        } catch (\Exception $e) {
            // Log the error but don't break the request
            Log::error('AuditLogMiddleware failed to log request: ' . $e->getMessage());
        }
    }

    /**
     * Determine the action based on the request
     */
    protected function determineAction(Request $request): string
    {
        $method = $request->method();

        // Determine action based on HTTP method
        if ($method === 'POST') {
            return 'create';
        }
        if (in_array($method, ['PUT', 'PATCH'])) {
            return 'update';
        }
        if ($method === 'DELETE') {
            return 'delete';
        }

        return strtolower($method);
    }

    /**
     * Determine the model type based on the request
     */
    protected function determineModelType(Request $request): ?string
    {
        $path = $request->path();

        // Extract model name from route
        if (preg_match('/api\/([^\/]+)/', $path, $matches)) {
            $modelName = $matches[1];

            // Convert to model class name
            $modelClass = 'App\\Models\\' . ucfirst(str_replace('-', '', $modelName));

            // Check if the model exists
            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        return null;
    }

    /**
     * Extract model ID from the request
     */
    protected function extractModelId(Request $request): ?string
    {
        $route = $request->route();

        if ($route && isset($route->parameters)) {
            // Try to get ID from route parameters
            foreach ($route->parameters as $key => $value) {
                if (in_array($key, ['id', 'user', 'auditLog'])) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Get old values for update operations
     */
    protected function getOldValues(Request $request, ?string $modelType, ?string $modelId): ?array
    {
        if (!$modelType || !$modelId || !in_array($request->method(), ['PUT', 'PATCH'])) {
            return null;
        }

        try {
            $model = $modelType::find($modelId);
            return $model ? $model->toArray() : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get new values from the request
     */
    protected function getNewValues(Request $request, Response $response): ?array
    {
        $method = $request->method();

        if ($method === 'DELETE') {
            return null;
        }

        // Get data from request
        $data = $request->all();

        // Remove sensitive fields
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'api_token'];
        foreach ($sensitiveFields as $field) {
            unset($data[$field]);
        }

        return !empty($data) ? $data : null;
    }

    /**
     * Generate a human-readable description
     */
    protected function generateDescription(Request $request, string $action, ?string $modelType): string
    {
        $method = $request->method();
        $path = $request->path();

        if ($modelType) {
            $modelName = class_basename($modelType);
            return ucfirst($action) . " {$modelName}";
        }

        return ucfirst($action) . " operation on {$path}";
    }

    /**
     * Determine if a failed request should be logged
     */
    protected function shouldLogFailedRequest(Request $request, Response $response): bool
    {
        // No longer handle failed login attempts here - handled by AuthController
        return false;
    }

    /**
     * Log a failed request
     */
    protected function logFailedRequest(Request $request, Response $response): void
    {
        // This method is no longer used - failed requests are handled by controllers
    }
}
