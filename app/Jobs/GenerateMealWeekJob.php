<?php

namespace App\Jobs;

use App\Models\MealPlan;
use App\Services\MealPlanGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateMealWeekJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    public function __construct(
        protected int $mealPlanId,
        protected int $weekNumber
    ) {}

    public function handle(MealPlanGenerator $generator): void
    {
        $plan = MealPlan::findOrFail($this->mealPlanId);

        if ($plan->status === 'cancelled') {
            Log::info("GenerateMealWeekJob early exit: Plan {$this->mealPlanId} is cancelled.");
            return;
        }

        // Update current week processing
        $plan->update(['current_week_processing' => $this->weekNumber]);

        try {
            $user = $plan->user;
            
            // For simplicity, we'll use 4 meals per day as a default or from user profile
            $mealsPerDay = $user->meals_per_day ?: 4;

            $structure = $generator->generateWeek(
                dailyCalories: $plan->daily_calories_target,
                proteinTarget: $plan->protein_g_target,
                carbsTarget: $plan->carbs_g_target,
                fatsTarget: $plan->fats_g_target,
                mealsPerDay: $mealsPerDay,
                allergies: $user->allergies_text,
                goalType: $user->goal_type,
                weekNumber: $this->weekNumber
            );

            DB::transaction(function () use ($plan, $structure) {
                $this->persistWeek($plan, $structure);
                
                $completedWeeks = $plan->weeks()->count();
                $progress = ($completedWeeks / 4) * 100;
                
                $plan->update([
                    'progress_percentage' => (int) $progress,
                    'status' => $completedWeeks >= 4 ? 'generated' : 'generating'
                ]);

                if ($completedWeeks >= 4) {
                    $plan->update([
                        'start_date' => $plan->weeks()->min('start_date'),
                        'end_date' => $plan->weeks()->max('end_date'),
                        'current_week_processing' => null
                    ]);
                }
            });

        } catch (\Throwable $e) {
            Log::error("Failed to generate week {$this->weekNumber} for plan {$this->mealPlanId}: " . $e->getMessage());
            
            if ($this->attempts() >= $this->tries) {
                $plan->update(['status' => 'failed']);
            }
            
            throw $e;
        }
    }

    private function persistWeek(MealPlan $plan, array $weekData): void
    {
        $week = $plan->weeks()->create([
            'week_number' => $this->weekNumber,
            // Assuming start_date/end_date logic can be simplified or dynamic
            'start_date' => now()->addWeeks($this->weekNumber - 1)->startOfWeek(),
            'end_date' => now()->addWeeks($this->weekNumber - 1)->endOfWeek(),
        ]);

        foreach (($weekData['days'] ?? []) as $dayData) {
            $day = $week->days()->create([
                'day_number' => $dayData['day_number'] ?? 1,
                'date' => $week->start_date->copy()->addDays(($dayData['day_number'] ?? 1) - 1),
            ]);

            foreach (($dayData['meals'] ?? []) as $mealData) {
                $meal = $day->meals()->create([
                    'meal_type' => $mealData['meal_type'] ?? 'breakfast',
                    'total_calories' => $mealData['total_calories'] ?? 0,
                    'total_protein_g' => $mealData['total_protein_g'] ?? 0,
                    'total_carbs_g' => $mealData['total_carbs_g'] ?? 0,
                    'total_fats_g' => $mealData['total_fats_g'] ?? 0,
                    'instructions_text' => $mealData['instructions_text'] ?? '',
                ]);

                foreach (($mealData['items'] ?? []) as $itemData) {
                    $meal->items()->create([
                        'food_name' => $itemData['food_name'],
                        'quantity' => $itemData['quantity'] ?? 0,
                        'unit' => $itemData['unit'] ?? 'g',
                        'calories' => $itemData['calories'] ?? 0,
                        'protein_g' => $itemData['protein_g'] ?? 0,
                        'carbs_g' => $itemData['carbs_g'] ?? 0,
                        'fats_g' => $itemData['fats_g'] ?? 0,
                    ]);
                }
            }
        }
    }
}
