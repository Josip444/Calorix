<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'sex',
        'age',
        'height_cm',
        'start_weight_kg',
        'current_weight_kg',
        'goal_weight_kg',
        'activity_level',
        'goal_type',
        'meals_per_day',
        'allergies_text',
        'daily_calories_target',
        'protein_g_target',
        'carbs_g_target',
        'fats_g_target',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function weightStats()
    {
        return $this->hasMany(UserWeightStat::class);
    }

    public function mealPlans()
    {
        return $this->hasMany(MealPlan::class);
    }
}
