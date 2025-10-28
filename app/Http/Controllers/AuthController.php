<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Log failed login attempt
            AuditLog::log(
                action: 'login_failed',
                description: "Failed login attempt for email: {$request->email}",
                userId: null, // No user ID for failed logins
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                url: $request->url(),
                method: $request->method()
            );

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        // Log successful login
        AuditLog::log(
            action: 'login',
            description: "Successful login for user: {$user->email}",
            userId: $user->id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            url: $request->url(),
            method: $request->method()
        );

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => false,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        // Log successful registration
        AuditLog::log(
            action: 'register',
            description: "User registration for: {$user->email}",
            userId: $user->id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            url: $request->url(),
            method: $request->method()
        );

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        // Log logout action before deleting token
        AuditLog::log(
            action: 'logout',
            description: "User logout for: {$user->email}",
            userId: $user->id,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            url: $request->url(),
            method: $request->method()
        );

        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
