<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class RegisteredUserController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'sex' => ['required', 'in:male,female,other'],
            'age' => ['required', 'integer', 'min:13', 'max:120'],
            'height_cm' => ['required', 'integer', 'min:100', 'max:250'],
            'start_weight_kg' => ['required', 'numeric', 'min:20', 'max:400'],
            'current_weight_kg' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'goal_weight_kg' => ['nullable', 'numeric', 'min:20', 'max:400'],
            'activity_level' => ['required', 'in:sedentary,light,moderate,active,very_active'],
            'goal_type' => ['required', 'in:lose,maintain,gain,build'],
            'meals_per_day' => ['required', 'integer', 'min:1', 'max:8'],
            'allergies_text' => ['nullable', 'string'],
        ]);

        if (empty($validated['current_weight_kg'])) {
            $validated['current_weight_kg'] = $validated['start_weight_kg'];
        }

        $bmr = $this->calculateBmr(
            $validated['sex'],
            (float) $validated['current_weight_kg'],
            (int) $validated['height_cm'],
            (int) $validated['age'],
        );

        $tdee = $this->applyActivityLevel($bmr, $validated['activity_level']);

        $dailyCalories = $this->adjustCaloriesForGoal($tdee, $validated['goal_type']);

        [$proteinGrams, $fatGrams, $carbGrams] = $this->calculateMacros(
            $dailyCalories,
            (float) $validated['current_weight_kg'],
            $validated['goal_type'],
        );

        $user = User::create([
            'name' => $validated['name'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'sex' => $validated['sex'],
            'age' => $validated['age'],
            'height_cm' => $validated['height_cm'],
            'start_weight_kg' => $validated['start_weight_kg'],
            'current_weight_kg' => $validated['current_weight_kg'],
            'goal_weight_kg' => $validated['goal_weight_kg'] ?? null,
            'activity_level' => $validated['activity_level'],
            'goal_type' => $validated['goal_type'],
            'meals_per_day' => $validated['meals_per_day'],
            'allergies_text' => $validated['allergies_text'] ?? null,
            'daily_calories_target' => $dailyCalories,
            'protein_g_target' => $proteinGrams,
            'carbs_g_target' => $carbGrams,
            'fats_g_target' => $fatGrams,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        $user->refresh();

        return response()->json([
            'user' => $user,
            'targets' => [
                'daily_calories' => $dailyCalories,
                'protein_g' => $proteinGrams,
                'carbs_g' => $carbGrams,
                'fats_g' => $fatGrams,
            ],
        ], 201);
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

