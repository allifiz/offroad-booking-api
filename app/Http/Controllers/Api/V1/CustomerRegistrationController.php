<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\Response;

class CustomerRegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30', 'unique:users,phone'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'role' => UserRole::CUSTOMER,
            'status' => UserStatus::ACTIVE,
        ]);

        $token = $user->createToken($validated['device_name'] ?? 'customer-web')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi customer berhasil.',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $token,
                'user' => $user,
            ],
        ], Response::HTTP_CREATED);
    }
}
