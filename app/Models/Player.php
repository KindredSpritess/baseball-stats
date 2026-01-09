<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $person_id
 * @property string|null $number
 * @property int $team_id
 * @property array<array-key, mixed>|null $stats
 * @property int $game_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BallInPlay> $ballsInPlay
 * @property-read int|null $balls_in_play_count
 * @property-read \App\Models\Game|null $game
 * @property-read \App\Models\Person|null $person
 * @property-read \App\Models\Team|null $team
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Player newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Player newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Player query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Player whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Player whereGameId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Player whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Player whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Player wherePersonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Player whereStats($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Player whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Player whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Player extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'person_id', 'team_id', 'game_id'];

    protected $with = ['person'];

    protected $casts = [
        'stats' => 'array',
    ];

    protected static $eventStats = [];

    public function person() {
        return $this->belongsTo(Person::class);
    }

    public function team() {
        return $this->belongsTo(Team::class);
    }

    public function game() {
        return $this->belongsTo(Game::class);
    }

    public function ballsInPlay() {
        return $this->hasMany(BallInPlay::class);
    }

    public function evt(string $stat): void {
        $stats = $this->stats ?? [];
        $stats[$stat] = ($this->stats[$stat] ?? 0) + 1;
        if (str_starts_with($stat, 'DO.')) {
            $stats['G.' . $stat[-1]] = 1;
        }
        $this->stats = $stats;
        self::$eventStats[] = ['player_id' => $this->id, 'stat' => $stat, 'value' => $stats[$stat]];
    }

    public static function getEventStats() {
        $stats = self::$eventStats;
        self::$eventStats = [];
        return $stats;
    }
}