<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Calendar\RescheduleGroupLessonService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        JsonResource::withoutWrapping();

        // ✅ Реєструємо Blade-директиву @money
        Blade::directive('money', function ($expression) {
            return "<?php echo number_format((float)($expression), 2, '.', ' ') . ' ₴'; ?>";
        });
    }
}
