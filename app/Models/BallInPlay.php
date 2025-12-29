<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

/**
 * @property int $id
 * @property int $player_id
 * @property int $play_id
 * @property array<array-key, mixed>|null $position
 * @property string|null $type
 * @property string|null $result
 * @property int|null $distance
 * @property array<array-key, mixed>|null $fielders
 * @property-read \App\Models\Play $play
 * @property-read \App\Models\Player $player
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallInPlay newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallInPlay newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallInPlay query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallInPlay whereDistance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallInPlay whereFielders($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallInPlay whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallInPlay wherePlayId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallInPlay wherePlayerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallInPlay wherePosition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallInPlay whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BallInPlay whereType($value)
 * @mixin \Eloquent
 */
class BallInPlay extends Model
{
    use HasFactory;
    use HasJsonRelationships;

    public $timestamps = false;

    /** 
     * @var bool lastPlay for frontend colouring.
     */
    public $lastPlay = false;

    protected $fillable = [
        'position',
        'type',
        'result',
        'fielders',
        'distance',
    ];

    protected $casts = [
        'position' => 'array',
        'fielders' => 'array',
    ];

    public function play(): BelongsTo {
        return $this->belongsTo(Play::class);
    }

    public function player(): BelongsTo {
        return $this->belongsTo(Player::class);
    }

    public function pitcher() {
        return $this->belongsToJson(Player::class, 'fielders->0');
    }
}
