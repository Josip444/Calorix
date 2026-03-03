<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealWeek extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_plan_id',
        'week_number',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function mealPlan()
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function days()
    {
        return $this->hasMany(MealDay::class);
    }
}

