<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_week_id',
        'day_number',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function week()
    {
        return $this->belongsTo(MealWeek::class, 'meal_week_id');
    }

    public function meals()
    {
        return $this->hasMany(Meal::class);
    }
}

