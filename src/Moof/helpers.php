<?php

use App\Actions\Moof\CreateBetaUser;
use App\Models\UnknownUser;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

if (!function_exists('🤟')) {

    /**
     * Translate the given message.
     *
     * @param  string  $engine  laravel|spatie default: laravel (spatie uses the spatie/markdown package) and laravel uses the laravel/markdown package
     */
    function 🤟(?string $key = null, array $replace = [], ?string $locale = null, array $markdown_options = [], bool $inline = false, string $engine = 'laravel'): string
    {
        // #TODO: add config('app.domain') . DIRECTORY_SEPARATOR to the key if the folder for the aap.domain exists
        // Str::sites(__(config('app.domain') .'.' .$lang_line)):

        $inlineMarkdown = ($engine === 'spatie') ? Str::spatieMarkdown($key) : Str::markdown(__($key, $replace, $locale), $markdown_options);

        return (!$inline) ?
            $inlineMarkdown :
            Str::inlineMarkdown(__($key, $replace, $locale), $markdown_options);
    }
}

if (!function_exists('moofUser')) {
    /**
     * Get the available auth instance.
     *
     * @return UnknownUser|Authenticatable
     */
    function moofUser(): UnknownUser|Authenticatable|User
    {
        if (auth()->user()) {
            $user = auth()->user();
        } else {
            if (auth('unknown_users')->user()) {
                $user = auth('unknown_users')->user();
            } else {
                $createBetaUserAction = new CreateBetaUser;
                $user = $createBetaUserAction->execute();
            }

        }

        $user->save();

        // ray($user);

        if (in_array('weatherapi.com', $user->consented_to['sites'] ?? [])) {
            if ($user->weatherResults()->doesntExist()) {

                // ray();

                $weather = Cache::store('redis')->remember('weatherapi:&q=31.120.193.89', (60 * 60 * 4), function () {
                    $endpoint = (string) 'http://api.weatherapi.com/v1/forecast.json?key=' . config('app.weather_api_key')
                        . '&q=31.120.193.89&days=1&aqi=no&alerts=no';
                    $response = Http::acceptJson()->get($endpoint);

                    Cache::store('redis')->decrement('weatherAPIcount');

                    // ray($response, $response->body(), $response->json(), $response->object());
                    return $response->json();
                });

                $user->weatherResults()->create([
                    'weather_dump' => $weather ?? json_encode([]),
                ]);

                $user->refresh();

            }
        }

        return $user;
    }
}

if (!function_exists('billingPortalUrl')) {
    function billingPortalUrl()
    {
        return route('billing');
    }
}
if (!function_exists('billingPortalButton')) {
    function billingPortalButton()
    {
        return '<a href="' . route('billing') . '" title="' . billingPortalUrl() . '" class="btn-primary">Stripe Billing Portal</a>';
    }
}

if (!function_exists('productButton')) {
    function productButton(string $version = 'pro', string $period = 'monthly', $title = 'Pro monthly Moof.digital subscription')
    {
        return sprintf(
            '<a href="%s" title="%s" class="btn-primary">%s</a>',
            route('product.subscription.checkout', [
                'version' => $version,
                'period' => $period,
            ]),
            route('product.subscription.checkout', [
                'period' => $period,
                'version' => $version,
            ]),
            __($title)
        );
    }
}

if (!function_exists('subscriptionButton')) {
    function subscriptionButton(string $version = 'basic', string $period = 'monthly', $title = 'Content subscription (monthly)'): string
    {
        return sprintf(
            '<a href="%s" title="%s" class="btn-primary">%s</a>',
            route('subscription.checkout', [
                'version' => $version,
                'period' => $period,
            ]),
            route('subscription.checkout', [
                'version' => $version,
                'period' => $period,
            ]),
            __($title)
        );
    }
}

if (!function_exists('getCallingMethodName')) {
    function getCallingMethodName()
    {
        $trace = debug_backtrace();
        if (isset($trace[2])) {
            if (isset($trace[2]['class'])) {
                return $trace[2]['class'] . '::' . $trace[2]['function'];
            } else {
                return $trace[2]['function'];
            }
        }

        return null;
    }
}

if (!function_exists('databaseExists')) {
    function databaseExists($databaseName): bool
    {
        $result = DB::select("SHOW DATABASES LIKE '$databaseName'");

        return count($result) > 0;
    }
}

if (!function_exists('devray')) {
    function devray(...$args): Spatie\Ray\Ray|Spatie\WordPressRay\Ray|Spatie\RayBundle\Ray|Spatie\YiiRay\Ray|Spatie\LaravelRay\Ray|null
    {
        if (!app()->environment('dev')) {
            return null;
        }

        return ray(...$args);
    }
}
