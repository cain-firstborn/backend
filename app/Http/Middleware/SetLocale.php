<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class SetLocale
{
    /**
     * Symfony's default Accept-Header locale value.
     */
    const SYMFONY_HEADER_LOCALE = 'en-us,en;q=0.5';

    /**
     * Create a new middleware instance.
     */
    public function __construct(private Application $app, private Repository $config)
    {
        //
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->parse($request->header('Accept-Language'));

        if (!in_array($locale, $this->config->get('app.supported_locale'))) {
            $locale = $this->config->get('app.fallback_locale');
        }

        $this->app->setLocale($locale);

        return $next($request);
    }

    /**
     * Parses the locale from the given Accept-Language header.
     */
    private function parse(string|null $locale): string
    {
        // In case the Accept-Header is the same as the Symfony's default,
        // we'll override it with so that the app default locale is used.
        $locale = $locale !== self::SYMFONY_HEADER_LOCALE ? $locale : $this->app->getLocale();

        return str($locale)
            ->before(',')
            ->before(';')
            ->before('-')
            ->toString();
    }
}
