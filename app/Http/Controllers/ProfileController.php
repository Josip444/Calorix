<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'sex' => $user->sex,
                'age' => $user->age,
                'height_cm' => $user->height_cm,
                'start_weight_kg' => $user->start_weight_kg,
                'current_weight_kg' => $user->current_weight_kg,
                'goal_weight_kg' => $user->goal_weight_kg,
                'activity_level' => $user->activity_level,
                'goal_type' => $user->goal_type,
                'meals_per_day' => $user->meals_per_day,
                'allergies_text' => $user->allergies_text,
                'daily_calories_target' => $user->daily_calories_target,
                'protein_g_target' => $user->protein_g_target,
                'carbs_g_target' => $user->carbs_g_target,
                'fats_g_target' => $user->fats_g_target,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'sex' => ['sometimes', 'in:male,female,other'],
            'age' => ['sometimes', 'integer', 'min:13', 'max:120'],
            'height_cm' => ['sometimes', 'integer', 'min:100', 'max:250'],
            'current_weight_kg' => ['sometimes', 'numeric', 'min:20', 'max:400'],
            'goal_weight_kg' => ['sometimes', 'nullable', 'numeric', 'min:20', 'max:400'],
            'activity_level' => ['sometimes', 'in:sedentary,light,moderate,active,very_active'],
            'goal_type' => ['sometimes', 'in:lose,maintain,gain,build'],
            'meals_per_day' => ['sometimes', 'integer', 'min:1', 'max:8'],
            'allergies_text' => ['sometimes', 'nullable', 'string'],
        ]);

        $original = $user->only([
            'current_weight_kg',
            'activity_level',
            'goal_type',
            'height_cm',
            'age',
        ]);

        $user->fill($data);

        $shouldRecalculate = $user->isDirty([
            'current_weight_kg',
            'activity_level',
            'goal_type',
            'height_cm',
            'age',
        ]);

        if ($shouldRecalculate) {
            $weight = (float) ($user->current_weight_kg ?? $user->start_weight_kg);

            $bmr = $this->calculateBmr(
                $user->sex,
                $weight,
                (int) $user->height_cm,
                (int) $user->age,
            );

            $tdee = $this->applyActivityLevel($bmr, $user->activity_level);

            $dailyCalories = $this->adjustCaloriesForGoal($tdee, $user->goal_type);

            [$proteinGrams, $fatGrams, $carbGrams] = $this->calculateMacros(
                $dailyCalories,
                $weight,
                $user->goal_type,
            );

            $user->daily_calories_target = $dailyCalories;
            $user->protein_g_target = $proteinGrams;
            $user->carbs_g_target = $carbGrams;
            $user->fats_g_target = $fatGrams;
        }

        $user->save();

        $user->refresh();

        return response()->json([
            'user' => $user,
        ]);
    }

    private function calculateBmr(string $sex, float $weightKg, int $heightCm, int $age): int
    {
        if ($sex === 'female') {
            $bmr = 10 * $weightKg + 6.25 * $heightCm - 5 * $age - 161;
        } else {
            $bmr = 10 * $weightKg + 6.25 * $heightCm - 5 * $age + 5;
        }

        return (int) round($bmr);
    }

    private function applyActivityLevel(int $bmr, string $activityLevel): int
    {
        $multipliers = [
            'sedentary' => 1.2,
            'light' => 1.375,
            'moderate' => 1.55,
            'active' => 1.725,
            'very_active' => 1.9,
        ];

        $multiplier = $multipliers[$activityLevel] ?? 1.2;

        return (int) round($bmr * $multiplier);
    }

    private function adjustCaloriesForGoal(int $tdee, string $goalType): int
    {
        $adjustments = [
            'lose' => -500,
            'maintain' => 0,
            'gain' => 300,
            'build' => 400,
        ];

        $adjustment = $adjustments[$goalType] ?? 0;

        $calories = $tdee + $adjustment;

        return max(1200, (int) round($calories));
    }

    private function calculateMacros(int $dailyCalories, float $weightKg, string $goalType): array
    {
        $proteinMultipliers = [
            'lose' => 2.0,
            'maintain' => 1.6,
            'gain' => 1.8,
            'build' => 2.2,
        ];

        $proteinPerKg = $proteinMultipliers[$goalType] ?? 1.6;

        $proteinGrams = (int) round($weightKg * $proteinPerKg);

        // 30% of calories from fat for all goals.
        $fatCalories = (int) round($dailyCalories * 0.30);
        $fatGrams = (int) round($fatCalories / 9);

        $remainingCalories = $dailyCalories - ($proteinGrams * 4 + $fatGrams * 9);
        $carbGrams = $remainingCalories > 0
            ? (int) round($remainingCalories / 4)
            : 0;

        return [$proteinGrams, $fatGrams, $carbGrams];
    }
}

