<?php

namespace App\Http\Controllers;

use App\Models\UserWeightStat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'start_weight_kg' => $user->start_weight_kg,
                'current_weight_kg' => $user->current_weight_kg,
                'goal_weight_kg' => $user->goal_weight_kg,
                'daily_calories_target' => $user->daily_calories_target,
                'protein_g_target' => $user->protein_g_target,
                'carbs_g_target' => $user->carbs_g_target,
                'fats_g_target' => $user->fats_g_target,
            ],
        ]);
    }

    public function storeWeight(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'weight' => ['required', 'numeric', 'min:20', 'max:400'],
        ]);

        $user = $request->user();

        $weightStat = UserWeightStat::create([
            'user_id' => $user->id,
            'date' => $data['date'],
            'weight' => $data['weight'],
        ]);

        $user->current_weight_kg = $data['weight'];
        $user->save();

        return response()->json([
            'user' => $user->fresh(),
            'weight_stat' => $weightStat,
        ], 201);
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = UserWeightStat::query()
            ->where('user_id', $user->id)
            ->orderBy('date')
            ->get(['id', 'date', 'weight', 'created_at', 'updated_at']);

        return response()->json([
            'weight_history' => $stats,
        ]);
    }
}

