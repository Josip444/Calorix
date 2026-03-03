<?php

namespace App\Services;

class MealPlanGenerator
{
    /**
     * Simulate an OpenAI-generated meal plan structure.
     *
     * This returns 4 weeks, each with 7 days, each day containing
     * $mealsPerDay meals with simple, deterministic macro values.
     */
    public function generate(
        int $dailyCalories,
        int $proteinTarget,
        int $carbsTarget,
        int $fatsTarget,
        int $mealsPerDay,
        ?string $allergies,
        string $goalType,
    ): array {
        $weeks = [];

        $startDate = now()->startOfDay();

        for ($w = 1; $w <= 4; $w++) {
            $weekStart = $startDate->copy()->addDays(($w - 1) * 7);
            $weekEnd = $weekStart->copy()->addDays(6);

            $days = [];

            for ($d = 1; $d <= 7; $d++) {
                $date = $weekStart->copy()->addDays($d - 1)->toDateString();

                $meals = [];

                $caloriesPerMeal = (int) floor($dailyCalories / $mealsPerDay);
                $proteinPerMeal = (int) floor($proteinTarget / $mealsPerDay);
                $carbsPerMeal = (int) floor($carbsTarget / $mealsPerDay);
                $fatsPerMeal = (int) floor($fatsTarget / $mealsPerDay);

                for ($m = 1; $m <= $mealsPerDay; $m++) {
                    $mealType = $this->mealTypeForIndex($m);

                    $items = [
                        [
                            'food_name' => ucfirst($mealType).' example '.$m,
                            'quantity' => 100,
                            'unit' => 'g',
                            'calories' => $caloriesPerMeal,
                            'protein_g' => $proteinPerMeal,
                            'carbs_g' => $carbsPerMeal,
                            'fats_g' => $fatsPerMeal,
                        ],
                    ];

                    $meals[] = [
                        'meal_type' => $mealType,
                        'total_calories' => $caloriesPerMeal,
                        'total_protein_g' => $proteinPerMeal,
                        'total_carbs_g' => $carbsPerMeal,
                        'total_fats_g' => $fatsPerMeal,
                        'instructions_text' => 'Simulated meal for '.$goalType.' goal.',
                        'items' => $items,
                    ];
                }

                $days[] = [
                    'day_number' => $d,
                    'date' => $date,
                    'meals' => $meals,
                ];
            }

            $weeks[] = [
                'week_number' => $w,
                'start_date' => $weekStart->toDateString(),
                'end_date' => $weekEnd->toDateString(),
                'days' => $days,
            ];
        }

        return [
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays(27)->toDateString(),
            'weeks' => $weeks,
        ];
    }

    private function mealTypeForIndex(int $index): string
    {
        return match ($index) {
            1 => 'breakfast',
            2 => 'lunch',
            3 => 'dinner',
            default => 'snack',
        };
    }
}

