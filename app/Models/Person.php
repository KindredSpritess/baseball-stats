<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $firstName
 * @property string $lastName
 * @property string $bats
 * @property string $throws
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BallInPlay> $ballsInPlay
 * @property-read int|null $balls_in_play_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Player> $players
 * @property-read int|null $players_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Person newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Person newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Person query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Person whereBats($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Person whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Person whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Person whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Person whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Person whereThrows($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Person whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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

    public function fullName(): string {
        return "{$this->lastName}, {$this->firstName[0]}";
    }
}
