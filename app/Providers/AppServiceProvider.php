<?php

namespace App\Providers;

use App\Listeners\CheckServicesHealth;
use App\Models\Content;
use App\Observers\ContentObserver;
use App\Rules\SafeUrl;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

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
        PasswordRule::defaults(function () {
            return PasswordRule::min(12)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });

        // /up also checks the database and the cache store.
        Event::listen(DiagnosingHealth::class, CheckServicesHealth::class);

        Event::listen(Lockout::class, function (Lockout $event) {
            Log::warning('Security Alert: Account Lockout triggered due to excessive failed login attempts.', [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'email' => request()->input('email'),
            ]);
        });

        $this->configureRateLimiting();
        $this->configureViewComposers();

        Content::observe(ContentObserver::class);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Rate Limiting
    // ──────────────────────────────────────────────────────────────────────────

    private function configureRateLimiting(): void
    {
        // General web traffic
        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Login attempts — keyed by email + IP and globally by IP to prevent brute-force & spraying
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by(Str::lower(trim((string) $request->input('email'))).'|'.$request->ip()),
                Limit::perMinute(20)->by($request->ip()),
            ];
        });

        // Search / filter endpoints
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Admin mutation endpoints (store / update / delete)
        RateLimiter::for('admin-actions', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Public comments / subscriber rate limiter
        RateLimiter::for('comment', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // Public donation attempts rate limiter
        RateLimiter::for('donation', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }

    // ──────────────────────────────────────────────────────────────────────────
    // View Composers
    // ──────────────────────────────────────────────────────────────────────────

    private function configureViewComposers(): void
    {
        // Only inject global data into the two partials that actually need it.
        // Bound specifically to header and footer partials instead of wildcard '*'.
        view()->composer(
            ['welcome', 'kontak', 'donasi', 'partials.site-header', 'partials.site-footer'],
            function ($view) {
                $contactData = Cache::remember('global_contact', 3600, fn () => $this->resolveContactData());
                $campaignData = Cache::remember('global_campaign', 3600, fn () => $this->resolveCampaignData());

                $view->with('globalContact', (object) $contactData);
                $view->with('globalCampaign', (object) $campaignData);
            }
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private Resolvers
    // ──────────────────────────────────────────────────────────────────────────

    private function resolveContactData(): array
    {
        $defaults = [
            'email' => 'walhijabar@gmail.com',
            'whatsapp' => '+62 821-1982-1159',
            'address' => 'Jl. Simponi No.29, Turangga, Kec. Lengkong, Kota Bandung, Jawa Barat 40264',
            'facebook' => 'https://facebook.com/walhi.jabar',
            'instagram' => 'https://instagram.com/walhi.jabar',
            'youtube' => 'https://www.youtube.com/@walhijabar',
            'twitter' => 'https://x.com/walhijabar',
        ];

        try {
            if (\Schema::hasTable('contents')) {
                $kontak = Content::where('category', 'kontak')
                    ->where('status', 'published')
                    ->first();

                if ($kontak?->body) {
                    $lines = explode("\n", str_replace("\r", '', $kontak->body));
                    foreach ($lines as $line) {
                        if (strpos($line, ':') !== false) {
                            [$key, $value] = explode(':', $line, 2);
                            $key = strtolower(trim($key));
                            // Values are escaped by Blade on output; only validate them here.
                            $value = trim($value);
                            if (array_key_exists($key, $defaults)) {
                                if ($key === 'email' && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                    continue;
                                }
                                if (in_array($key, ['facebook', 'instagram', 'youtube', 'twitter'], true)
                                    && ! SafeUrl::isSafe($value, allowRelative: false)) {
                                    continue;
                                }
                                $defaults[$key] = $value;
                            }
                        }
                    }
                }
            }
        } catch (\Exception) {
            // Fail silently during migrations / CLI commands
        }

        return $defaults;
    }

    private function resolveCampaignData(): array
    {
        $defaults = [
            'title' => 'Kampanye Darurat: Hentikan Tambang Ilegal',
            'url' => '#',
        ];

        try {
            if (\Schema::hasTable('contents')) {
                $campaign = Content::where('category', 'kampanye-darurat')
                    ->where('status', 'published')
                    ->first();

                if ($campaign) {
                    $defaults['title'] = $campaign->title;
                    $defaults['url'] = SafeUrl::sanitize($campaign->tags);
                }
            }
        } catch (\Exception) {
            // Fail silently during migrations / CLI commands
        }

        return $defaults;
    }
}
