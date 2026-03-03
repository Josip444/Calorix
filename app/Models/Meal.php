<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_day_id',
        'meal_type',
        'total_calories',
        'total_protein_g',
        'total_carbs_g',
        'total_fats_g',
        'instructions_text',
    ];

    public function day()
    {
        return $this->belongsTo(MealDay::class, 'meal_day_id');
    }

    public function items()
    {
        return $this->hasMany(MealItem::class);
    }
}

