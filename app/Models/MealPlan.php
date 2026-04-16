<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function weeks()
    {
        return $this->hasMany(MealWeek::class);
    }
}

