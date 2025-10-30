<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'short_name',
        'name',
        'season',
        'primary_color',
        'secondary_color',
    ];

    public function players() {
        return $this->hasMany(Player::class);
    }

    public function games() {
        return $this->hasMany(Game::class, 'home')->orWhere('away', $this->id);
    }
}
