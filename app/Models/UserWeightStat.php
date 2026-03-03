<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserWeightStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'weight',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

