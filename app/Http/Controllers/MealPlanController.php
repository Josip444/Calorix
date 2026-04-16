<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateMealWeekJob;
use App\Models\Meal;
use App\Models\MealDay;
use App\Models\MealPlan;
use App\Models\MealWeek;
use App\Services\MealPlanGenerator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MealPlanController extends Controller
{
    public function __construct(private MealPlanGenerator $generator)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $plans = MealPlan::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get([
                'id',
                'start_date',
                'end_date',
                'daily_calories_target',
                'protein_g_target',
                'carbs_g_target',
                'fats_g_target',
                'source',
                'status',
                'current_week_processing',
                'progress_percentage',
                'created_at',
            ]);

        return response()->json([
            'meal_plans' => $plans,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'meals_per_day' => ['sometimes', 'integer', 'min:1', 'max:8'],
        ]);

        $mealsPerDay = (int) ($request->input('meals_per_day') ?: $user->meals_per_day);

        $plan = MealPlan::create([
            'user_id' => $user->id,
            'daily_calories_target' => $user->daily_calories_target,
            'protein_g_target' => $user->protein_g_target,
            'carbs_g_target' => $user->carbs_g_target,
            'fats_g_target' => $user->fats_g_target,
            'source' => 'openai',
            'status' => 'generated', // Set to generated immediately as skeleton is ready
            'progress_percentage' => 0,
        ]);

        // Create 4 empty weeks
        for ($week = 1; $week <= 4; $week++) {
            $plan->weeks()->create([
                'week_number' => $week,
                'start_date' => now()->addWeeks($week - 1)->startOfWeek(),
                'end_date' => now()->addWeeks($week - 1)->endOfWeek(),
            ]);
        }

        return response()->json([
            'message' => 'Plan prehrane je kreiran. Sada možete generirati obroke za svaki tjedan.',
            'meal_plan' => $plan->load('weeks'),
        ], 201);
    }

    public function generateWeek(MealPlan $mealPlan, MealWeek $week): JsonResponse
    {
        $this->authorizeForUserOrFail($mealPlan);

        if ($week->meal_plan_id !== $mealPlan->id) {
            abort(404);
        }

        // Optional: Check if already generating or has content
        if ($mealPlan->status === 'generating' && $mealPlan->current_week_processing === $week->week_number) {
            return response()->json(['message' => 'Ovaj tjedan se već generira.'], 422);
        }

        $mealPlan->update([
            'status' => 'generating',
            'current_week_processing' => $week->week_number
        ]);

        GenerateMealWeekJob::dispatch($mealPlan->id, $week->week_number);

        return response()->json([
            'message' => "Generiranje za tjedan {$week->week_number} je započelo.",
            'meal_plan' => $mealPlan->load('weeks'),
        ]);
    }

    public function show(MealPlan $mealPlan): JsonResponse
    {
        $this->authorizeForUserOrFail($mealPlan);

        $mealPlan->load('weeks.days.meals.items');

        return response()->json([
            'meal_plan' => $mealPlan,
        ]);
    }

    public function cancel(MealPlan $mealPlan): JsonResponse
    {
        $this->authorizeForUserOrFail($mealPlan);

        if ($mealPlan->status === 'generating') {
            $mealPlan->update([
                'status' => 'generated', // Revert to generated, not cancelled
                'current_week_processing' => null // Clear current week to signal the job to stop
            ]);
            Log::info("MealPlan {$mealPlan->id} weekly generation was stopped by user.");
        }

        return response()->json([
            'message' => 'Generiranje za ovaj tjedan je otkazano.',
            'meal_plan' => $mealPlan->load('weeks'),
        ]);
    }

    public function destroy(MealPlan $mealPlan): JsonResponse
    {
        $this->authorizeForUserOrFail($mealPlan);

        $mealPlan->delete();

        return response()->json([
            'message' => 'Plan prehrane je uspješno obrisan.',
        ]);
    }

    public function showWeek(MealPlan $mealPlan, MealWeek $week): JsonResponse
    {
        $this->authorizeForUserOrFail($mealPlan);

        if ($week->meal_plan_id !== $mealPlan->id) {
            abort(404);
        }

        $week->load('days.meals.items');

        return response()->json([
            'week' => $week,
        ]);
    }

    public function showDay(MealPlan $mealPlan, MealDay $day): JsonResponse
    {
        $this->authorizeForUserOrFail($mealPlan);

        if ($day->week->meal_plan_id !== $mealPlan->id) {
            abort(404);
        }

        $day->load('meals.items');

        return response()->json([
            'day' => $day,
        ]);
    }

    public function showMeal(Meal $meal): JsonResponse
    {
        $meal->load('day.week.mealPlan', 'items');

        $mealPlan = $meal->day->week->mealPlan;

        $this->authorizeForUserOrFail($mealPlan);

        return response()->json([
            'meal' => $meal,
        ]);
    }

    private function authorizeForUserOrFail(MealPlan $mealPlan): void
    {
        $userId = auth()->id();

        if ($mealPlan->user_id !== $userId) {
            abort(404);
        }
    }
}

