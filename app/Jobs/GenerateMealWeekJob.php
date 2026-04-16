<?php

namespace App\Jobs;

use App\Models\MealPlan;
use App\Models\MealWeek;
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
    public $timeout = 180;

    public function __construct(
        protected int $mealPlanId,
        protected int $weekNumber
    ) {}

    public function handle(MealPlanGenerator $generator): void
    {
        $plan = MealPlan::findOrFail($this->mealPlanId);

        // Update current week processing
        $plan->update(['current_week_processing' => $this->weekNumber]);

        if ($plan->status === 'cancelled' || $plan->current_week_processing !== $this->weekNumber) {
            Log::info("GenerateMealWeekJob early exit: Plan {$this->mealPlanId} is cancelled or processing another week.");
            return;
        }

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

            // Check again after OpenAI response
            $plan->refresh();
            if ($plan->status === 'cancelled' || $plan->current_week_processing !== $this->weekNumber) {
                Log::info("GenerateMealWeekJob exit after AI: Plan {$this->mealPlanId} was stopped for week {$this->weekNumber}.");
                return;
            }

            DB::transaction(function () use ($plan, $structure, $generator) {
                $generator->persistWeek($plan, $this->weekNumber, $structure);
                
                // Use a fresh query for accuracy
                $completedWeeks = MealWeek::where('meal_plan_id', $plan->id)
                    ->whereHas('days')
                    ->count();
                
                $progress = ($completedWeeks / 4) * 100;
                
                $plan->update([
                    'progress_percentage' => (int) $progress,
                    'status' => $completedWeeks >= 4 ? 'generated' : 'generating',
                    'current_week_processing' => null
                ]);

                if ($completedWeeks >= 4) {
                    $plan->update([
                        'start_date' => $plan->weeks()->min('start_date'),
                        'end_date' => $plan->weeks()->max('end_date'),
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
}
