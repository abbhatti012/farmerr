<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\Admin; // adjust namespace if your Admin model is elsewhere

class AdminApiAuthController extends Controller
{
    /**
     * Login and return API token (Sanctum).
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|min:6',
            // optionally 'device_name' => 'string' to name tokens per-client
        ]);

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        // Attempt to authenticate using the admin guard
        if (! Auth::guard('admin')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        // Create a personal access token (Sanctum) — include device_name if provided
        $deviceName = $request->input('device_name', 'api-token');

        $token = $admin->createToken($deviceName)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful.',
            'data' => [
                'admin' => [
                    'id' => $admin->id,
                    'name' => $admin->name ?? null,
                    'email' => $admin->email,
                    // include other non-sensitive fields if desired
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                // optionally: 'expires_at' => null (Sanctum tokens do not expire by default)
            ],
        ], 200);
    }

    /**
     * Logout (revoke current token)
     */
    public function logout(Request $request)
    {
        /** @var Admin $admin */
        $admin = $request->user(); // when using auth:sanctum, this will be the admin model

        if ($admin) {
            // Revoke current access token
            $request->user()->currentAccessToken()->delete();

            // Or to revoke all tokens:
            // $request->user()->tokens()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out and token revoked.',
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'No authenticated admin.',
        ], 401);
    }

    /**
     * Optional: get current admin info
     */
    public function me(Request $request)
    {
        $admin = $request->user();

        return response()->json([
            'status' => 'success',
            'data' => [
                'admin' => $admin,
            ],
        ], 200);
    }
}
