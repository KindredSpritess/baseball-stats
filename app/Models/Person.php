<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Person extends Model
{
    use HasFactory;

    protected $fillable = ['firstName', 'lastName', 'bats', 'throws'];

    public function players(): HasMany {
        return $this->hasMany(Player::class);
    }

    public function ballsInPlay(): HasManyThrough {
        return $this->hasManyThrough(BallInPlay::class, Player::class);
    }
}
