<?php

namespace App\Providers;

use App\Helpers\StatsHelper;
use Illuminate\Support\Facades\Blade;
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
    }
}
