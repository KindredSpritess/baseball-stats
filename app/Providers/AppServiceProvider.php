<?php

namespace App\Providers;

use App\Helpers\StatsHelper;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::define('score-game', function (User $user, Game $game) {
            return $user->role === 'superuser' || $user->teams()->whereIn('team_id', [$game->home, $game->away])->exists();
        });
        Gate::define('score', function (User $user) {
            return in_array($user->role, ['scorer', 'superuser']);
        });
        Gate::define('create-game', function (User $user) {
            return $user->role === 'superuser';
        });
        Gate::define('create-team', function (User $user) {
            return $user->role === 'superuser';
        });
        Gate::define('edit-team', function (User $user, $team) {
            return $user->role === 'superuser' || $user->teams()->where('team_id', $team->id)->exists();
        });
        Gate::define('manage-season', function (User $user, $season) {
            return $user->role === 'superuser' || $user->seasons()->where('season_id', $season->id)->exists();
        });
        Gate::define('import-roster', function (User $user, $team) {
            if ($user->role === 'superuser') {
                return true;
            }
            
            $isTeamScorer = $user->teams()->where('team_id', $team->id)->exists();
            $isSeasonAdmin = $team->season_id ? $user->seasons()->where('season_id', $team->season_id)->exists() : false;
            return $isTeamScorer || $isSeasonAdmin;
        });

        Blade::directive('spaceless', function () {
            return '<?php ob_start(); ?>';
        });
        Blade::directive('endspaceless', function () {
            return '<?php echo preg_replace("/[\s\n]+</m", "<", ob_get_clean()); ?>';
        });
        Blade::directive('stat', function(string $stat) {
            return "<?php echo \$stats->humanStat({$stat}); ?>";
        });
        Blade::directive('playersStat', function (string $stat) {
            return <<<PHP
                <?php
                    echo collect(\$lineup)
                        ->flatten()
                        ->concat(\$pitchers)
                        ->unique('id')
                        ->filter(fn(\$player) => \$stats[\$player->id]->stat({$stat}))
                        ->map(fn (\$player) => \$player->person->lastName . (\$stats[\$player->id]->stat({$stat}) > 1 ? ' ' . \$stats[\$player->id]->stat({$stat}) : ''))
                        ->join(', ');
                ?>
            PHP;
        });
        Blade::directive('pitch', function (string $pitch) {
            return <<<PHP
                <?php
                    echo match ($pitch) {
                        's' => 'Swinging Strike',
                        'c' => 'Called Strike',
                        '.' => 'Ball',
                        'b' => 'Ball in dirt',
                        'x' => 'In Play',
                        't' => 'Foul Tip',
                        'r' => 'Foul (runner going)',
                        'f' => 'Foul',
                        default => "Unknown Pitch $pitch"
                    };
                ?>
            PHP;
        });
    }
}
