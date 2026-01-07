<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $short_name
 * @property string $name
 * @property string|null $season
 * @property int|null $season_id
 * @property string|null $primary_color
 * @property string|null $secondary_color
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Game> $games
 * @property-read int|null $games_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Player> $players
 * @property-read int|null $players_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team wherePrimaryColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereSeason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereSecondaryColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereShortName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'short_name',
        'name',
        'season_id',
        'primary_color',
        'secondary_color',
    ];

    public function players() {
        return $this->hasMany(Player::class);
    }

    public function season() {
        return $this->belongsTo(Season::class);
    }

    public function games() {
        return $this->hasMany(Game::class, 'home')->orWhere('away', $this->id);
    }

    public function users() {
        return $this->belongsToMany(User::class);
    }
}
