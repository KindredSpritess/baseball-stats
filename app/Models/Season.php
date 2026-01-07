<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'scoring_rules',
    ];

    protected $casts = [
        'scoring_rules' => 'array',
    ];

    public function teams()
    {
        return $this->hasMany(Team::class);
    }
}