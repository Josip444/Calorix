<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\MealDay;
use App\Models\MealPlan;
use App\Models\MealWeek;
use App\Services\MealPlanGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'source' => 'simulated',
            'status' => 'generating',
        ]);

        try {
            $structure = $this->generator->generate(
                dailyCalories: $user->daily_calories_target,
                proteinTarget: $user->protein_g_target,
                carbsTarget: $user->carbs_g_target,
                fatsTarget: $user->fats_g_target,
                mealsPerDay: $mealsPerDay,
                allergies: $user->allergies_text,
                goalType: $user->goal_type,
            );

            $this->persistStructure($plan, $structure);

            $plan->status = 'generated';
            $plan->start_date = $structure['start_date'];
            $plan->end_date = $structure['end_date'];
            $plan->save();
        } catch (\Throwable $e) {
            $plan->status = 'failed';
            $plan->save();

            return response()->json([
                'message' => 'Failed to generate meal plan.',
            ], 500);
        }

        $plan->load('weeks.days.meals.items');

        return response()->json([
            'meal_plan' => $plan,
        ], 201);
    }

    public function show(MealPlan $mealPlan): JsonResponse
    {
        $this->authorizeForUserOrFail($mealPlan);

        $mealPlan->load('weeks');

        return response()->json([
            'meal_plan' => $mealPlan,
        ]);
    }

    public function showWeek(MealPlan $mealPlan, MealWeek $week): JsonResponse
    {
        $this->authorizeForUserOrFail($mealPlan);

        if ($week->meal_plan_id !== $mealPlan->id) {
            abort(404);
        }

        $week->load('days');

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

        $day->load('meals');

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

    private function persistStructure(MealPlan $plan, array $structure): void
    {
        foreach ($structure['weeks'] as $weekData) {
            $week = $plan->weeks()->create([
                'week_number' => $weekData['week_number'],
                'start_date' => $weekData['start_date'],
                'end_date' => $weekData['end_date'],
            ]);

            foreach ($weekData['days'] as $dayData) {
                $day = $week->days()->create([
                    'day_number' => $dayData['day_number'],
                    'date' => $dayData['date'],
                ]);

                foreach ($dayData['meals'] as $mealData) {
                    $meal = $day->meals()->create([
                        'meal_type' => $mealData['meal_type'],
                        'total_calories' => $mealData['total_calories'],
                        'total_protein_g' => $mealData['total_protein_g'],
                        'total_carbs_g' => $mealData['total_carbs_g'],
                        'total_fats_g' => $mealData['total_fats_g'],
                        'instructions_text' => $mealData['instructions_text'],
                    ]);

                    foreach ($mealData['items'] as $itemData) {
                        $meal->items()->create([
                            'food_name' => $itemData['food_name'],
                            'quantity' => $itemData['quantity'],
                            'unit' => $itemData['unit'],
                            'calories' => $itemData['calories'],
                            'protein_g' => $itemData['protein_g'],
                            'carbs_g' => $itemData['carbs_g'],
                            'fats_g' => $itemData['fats_g'],
                        ]);
                    }
                }
            }
        }
    }
}

