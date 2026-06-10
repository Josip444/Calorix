<?php

namespace Tests\Feature\Jobs;

use App\Jobs\GenerateMealWeekJob;
use App\Models\MealPlan;
use App\Models\User;
use App\Services\MealPlanGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Mockery;

class GenerateMealWeekJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sets_status_to_generated_after_completing_one_week()
    {
        // 1. Setup User and Plan
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'sex' => 'male',
            'age' => 30,
            'height_cm' => 180,
            'start_weight_kg' => 80,
            'current_weight_kg' => 80,
            'goal_weight_kg' => 75,
            'activity_level' => 'moderate',
            'meals_per_day' => 3,
            'goal_type' => 'maintain',
            'allergies_text' => null
        ]);

        $plan = MealPlan::create([
            'user_id' => $user->id,
            'daily_calories_target' => 2000,
            'protein_g_target' => 150,
            'carbs_g_target' => 200,
            'fats_g_target' => 70,
            'status' => 'generating',
            'progress_percentage' => 0,
            'current_week_processing' => 1
        ]);

        // Create 4 empty weeks
        for ($i = 1; $i <= 4; $i++) {
            $plan->weeks()->create([
                'week_number' => $i,
                'start_date' => now()->addWeeks($i-1)->startOfWeek(),
                'end_date' => now()->addWeeks($i-1)->endOfWeek(),
            ]);
        }

        // 2. Mock Generator
        $generator = Mockery::mock(MealPlanGenerator::class);
        $generator->shouldReceive('generateWeek')->once()->andReturn(['days' => []]);
        $generator->shouldReceive('persistWeek')->once()->andReturnUsing(function($plan, $weekNum, $data) {
            // Simulate persisting by creating at least one day to satisfy whereHas('days')
            $week = $plan->weeks()->where('week_number', $weekNum)->first();
            $week->days()->create(['day_number' => 1, 'date' => now()]);
        });

        // 3. Run Job
        $job = new GenerateMealWeekJob($plan->id, 1);
        $job->handle($generator);

        // 4. Assert status is 'generated' and current_week_processing is null
        $plan->refresh();
        $this->assertEquals('generated', $plan->status);
        $this->assertNull($plan->current_week_processing);
        $this->assertEquals(25, $plan->progress_percentage); // 1 out of 4 weeks
    }
}
