<?php

return [
    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The fully qualified class name of the User model used in your application.
    | This is used for establishing relationships with audit logs.
    |
    */
    'user_model' => env('AUDIT_CENTER_USER_MODEL', \App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the audit log API routes.
    |
    */
    'routes' => [
        /*
        |--------------------------------------------------------------------------
        | Route Prefix
        |--------------------------------------------------------------------------
        |
        | The prefix for all audit log routes. Default is 'audit-logs'.
        | Note: The ServiceProvider automatically adds 'api' prefix, so this
        | should not include 'api/' to avoid duplicate prefixes.
        |
        */
        'prefix' => env('AUDIT_CENTER_ROUTE_PREFIX', 'audit-logs'),

        /*
        |--------------------------------------------------------------------------
        | Route Middleware
        |--------------------------------------------------------------------------
        |
        | Middleware groups to apply to audit log routes. Default requires
        | authentication and admin access.
        |
        */
        'middleware' => env('AUDIT_CENTER_ROUTE_MIDDLEWARE', ['auth:sanctum', 'admin']),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the audit logging middleware that automatically logs
    | API requests.
    |
    */
    'middleware' => [
        /*
        |--------------------------------------------------------------------------
        | API Prefix
        |--------------------------------------------------------------------------
        |
        | The API prefix to match for automatic logging. Default is 'api'.
        |
        */
        'api_prefix' => env('AUDIT_CENTER_API_PREFIX', 'api'),

        /*
        |--------------------------------------------------------------------------
        | Logged HTTP Methods
        |--------------------------------------------------------------------------
        |
        | HTTP methods that should be automatically logged by the middleware.
        |
        */
        'logged_methods' => env('AUDIT_CENTER_LOGGED_METHODS', ['POST', 'PUT', 'PATCH', 'DELETE']),

        /*
        |--------------------------------------------------------------------------
        | Excluded Routes
        |--------------------------------------------------------------------------
        |
        | Routes that should be excluded from automatic audit logging.
        | Use wildcards (*) to match patterns.
        |
        */
        'excluded_routes' => [
            'api/audit-logs*', // Exclude audit log endpoints to avoid recursion
            'api/user', // Exclude user endpoint to avoid spam
            'api/login', // Exclude login - typically handled by AuthController
            'api/register', // Exclude register - typically handled by AuthController
            'api/logout', // Exclude logout - typically handled by AuthController
        ],

        /*
        |--------------------------------------------------------------------------
        | Sensitive Fields
        |--------------------------------------------------------------------------
        |
        | Fields that should be excluded from audit log new_values to protect
        | sensitive information.
        |
        */
        'sensitive_fields' => [
            'password',
            'password_confirmation',
            'token',
            'api_token',
            'remember_token',
        ],

        /*
        |--------------------------------------------------------------------------
        | Auto-register Middleware
        |--------------------------------------------------------------------------
        |
        | Whether to automatically register the audit logging middleware.
        | If false, you'll need to manually register it in your application's
        | middleware configuration.
        |
        */
        'auto_register' => env('AUDIT_CENTER_AUTO_REGISTER_MIDDLEWARE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Vue.js frontend components.
    |
    */
    'frontend' => [
        /*
        |--------------------------------------------------------------------------
        | Frontend Route
        |--------------------------------------------------------------------------
        |
        | The frontend route path for the audit logs page.
        |
        */
        'route' => env('AUDIT_CENTER_FRONTEND_ROUTE', '/audit-logs'),

        /*
        |--------------------------------------------------------------------------
        | API Endpoint Prefix
        |--------------------------------------------------------------------------
        |
        | The API endpoint prefix used by Vue components. Should match the
        | route prefix above. Note: Since ApiService typically has baseURL '/api',
        | this should be 'audit-logs' (without leading /) to avoid duplicate prefixes.
        | The frontend will automatically remove the leading '/' if present.
        |
        */
        'api_prefix' => env('AUDIT_CENTER_FRONTEND_API_PREFIX', 'audit-logs'),
    ],
];

