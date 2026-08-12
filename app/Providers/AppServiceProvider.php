<?php

namespace App\Providers;

use App\Models\Setting;
use Carbon\Carbon;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Assets\Js;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
        FilamentAsset::register([
            Js::make('custom-script', asset('js/customs/tab-refresh.js')),
        ]);

        // set timezone based on Settings
        // $timezone = Setting::where('key', 'timezone')->value('value');
        // if ($timezone) {
        //     config(['app.timezone' => $timezone]);
        //     date_default_timezone_set($timezone);
        // } else {
        //     // Fallback to the default timezone if not set
        //     date_default_timezone_set(config('app.timezone'));
        // }

        // Override SMTP config from settings table (key = 'smtp')
        // Expected JSON structure:
        // {
        //   "host": "...",
        //   "port": "1025",
        //   "username": "...",
        //   "password": null,
        //   "from_address": "...",
        //   "from_name": "..."
        // }
        try {
            if (Schema::hasTable('settings')) {
                $smtp = Setting::query()->where('key', 'smtp')->value('value');
                if (is_array($smtp) && ! empty($smtp)) {
                    $updates = [];

                    if (isset($smtp['host']) && $smtp['host']) {
                        $updates['mail.mailers.smtp.host'] = $smtp['host'];
                        // Ensure SMTP is the default mailer when host provided
                        $updates['mail.default'] = 'smtp';
                    }
                    if (isset($smtp['port']) && $smtp['port'] !== '') {
                        $updates['mail.mailers.smtp.port'] = (int) $smtp['port'];
                    }
                    if (array_key_exists('username', $smtp)) {
                        $updates['mail.mailers.smtp.username'] = $smtp['username'];
                    }
                    if (array_key_exists('password', $smtp)) {
                        $updates['mail.mailers.smtp.password'] = $smtp['password'];
                    }
                    if (array_key_exists('from_address', $smtp)) {
                        $updates['mail.from.address'] = $smtp['from_address'];
                    }
                    if (array_key_exists('from_name', $smtp)) {
                        $updates['mail.from.name'] = $smtp['from_name'];
                    }

                    if (! empty($updates)) {
                        config($updates);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore during early bootstrap or before migrations
        }
    }
}
