<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $fillable = ['number'];

    protected $casts = [
        'stats' => 'array',
    ];

    public function person() {
        return $this->belongsTo(Person::class);
    }

    public function team() {
        return $this->belongsTo(Team::class);
    }

    public function game() {
        return $this->belongsTo(Game::class);
    }

    public function evt(string $stat): void {
        $stats = $this->stats ?? [];
        $stats[$stat] = ($this->stats[$stat] ?? 0) + 1;
        if (str_starts_with($stat, 'DO.')) {
            $stats['G.' . $stat[-1]] = 1;
        }
        $this->stats = $stats;
    }
}