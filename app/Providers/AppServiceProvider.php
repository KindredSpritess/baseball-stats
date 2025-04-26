<?php

namespace App\Providers;

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
            return '<?php echo preg_replace("/>[\s\n]+</m", "><", ob_get_clean()); ?>';
        });
    }
}
