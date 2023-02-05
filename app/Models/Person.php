<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $fillable = ['firstName', 'lastName', 'bats', 'throws'];

    public function players() {
        return $this->hasMany(Player::class);
    }
}
