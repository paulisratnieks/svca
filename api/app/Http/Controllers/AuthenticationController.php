<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthenticationController extends Controller
{
    public function authenticate(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            session()->regenerate();

            return response()->json([
                'message' => 'Login successful',
            ]);
        }

        return response()->json([
            'message' => 'Invalid login credentials',
        ], 401);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        User::create([
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'name' => $request->validated('name'),
        ]);

        return response()->json(status: 201);
    }
}
