<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('daily_calories_target');
            $table->unsignedSmallInteger('protein_g_target');
            $table->unsignedSmallInteger('carbs_g_target');
            $table->unsignedSmallInteger('fats_g_target');
            $table->string('source')->default('openai');
            $table->enum('status', ['generated', 'failed', 'generating'])->default('generating');
            $table->timestamps();
        });

        Schema::create('meal_weeks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('week_number');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });

        Schema::create('meal_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_week_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number');
            $table->date('date');
            $table->timestamps();
        });

        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_day_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack']);
            $table->unsignedInteger('total_calories');
            $table->unsignedSmallInteger('total_protein_g');
            $table->unsignedSmallInteger('total_carbs_g');
            $table->unsignedSmallInteger('total_fats_g');
            $table->text('instructions_text')->nullable();
            $table->timestamps();
        });

        Schema::create('meal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('food_name');
            $table->decimal('quantity', 8, 2);
            $table->enum('unit', ['g', 'ml', 'piece']);
            $table->unsignedInteger('calories');
            $table->unsignedSmallInteger('protein_g');
            $table->unsignedSmallInteger('carbs_g');
            $table->unsignedSmallInteger('fats_g');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_items');
        Schema::dropIfExists('meals');
        Schema::dropIfExists('meal_days');
        Schema::dropIfExists('meal_weeks');
        Schema::dropIfExists('meal_plans');
    }
};

