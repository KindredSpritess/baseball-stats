<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BallInPlay extends Model
{
    use HasFactory;

    public $timestamps = false;

    /** 
     * @var bool lastPlay for frontend colouring.
     */
    public $lastPlay = false;

    protected $fillable = [
        'position',
    ];

    protected $casts = [
        'position' => 'array',
    ];

    public function play(): BelongsTo {
        return $this->belongsTo(Play::class);
    }

    public function player(): BelongsTo {
        return $this->belongsTo(Player::class);
    }
}
