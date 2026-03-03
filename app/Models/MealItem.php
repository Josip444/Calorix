<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MealItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_id',
        'food_name',
        'quantity',
        'unit',
        'calories',
        'protein_g',
        'carbs_g',
        'fats_g',
    ];

    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }
}

