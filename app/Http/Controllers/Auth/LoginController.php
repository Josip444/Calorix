<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, remember: true)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $request->session()->regenerate();

        $user = $request->user()->fresh();

        $targets = [
            'daily_calories' => $user->daily_calories_target,
            'protein_g' => $user->protein_g_target,
            'carbs_g' => $user->carbs_g_target,
            'fats_g' => $user->fats_g_target,
        ];

        return response()->json([
            'user' => $user,
            'targets' => $targets,
        ]);
    }
}

