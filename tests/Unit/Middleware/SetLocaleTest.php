<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\SetLocale;
use Illuminate\Config\Repository as Config;
use Illuminate\Contracts\Container\BindingResolutionException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use ReflectionException;
use ReflectionMethod;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    /**
     * Middleware instance.
     */
    private SetLocale $middleware;

    /**
     * Set up the test environment.
     *
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new SetLocale($this->app, $this->app->make(Config::class));
    }

    #[Test]
    #[DataProvider('scenarios')]
    public function it_parses_locale(string|null $input, string $actual): void
    {
        $this->assertEquals($actual, $this->parse($input));
    }

    #[Test]
    public function it_returns_default_for_symfony_header(): void
    {
        $this->app->setLocale('fr');

        $header = SetLocale::SYMFONY_HEADER_LOCALE;

        $this->assertEquals('fr', $this->parse($header));
    }

    /**
     * Parses the locale from the given Accept-Language header.
     *
     * @throws ReflectionException
     */
    private function parse(string|null $locale): string
    {
        $method = new ReflectionMethod(SetLocale::class, 'parse');

        return $method->invoke($this->middleware, $locale);
    }

    /**
     * Set of locale scenarios.
     */
    public static function scenarios(): array
    {
        return [
            'basic locales'               => [
                'input'  => 'en',
                'actual' => 'en',
            ],
            'region codes'                => [
                'input'  => 'en-US',
                'actual' => 'en',
            ],
            'quality weights'             => [
                'input'  => 'de,en;q=0.9',
                'actual' => 'de',
            ],
            'quality weights with region' => [
                'input'  => 'it-IT;q=0.8,en;q=0.5',
                'actual' => 'it',
            ],
            'multiple languages'          => [
                'input'  => 'es-ES,es;q=0.9,en;q=0.8',
                'actual' => 'es',
            ],
            'malformed input'             => [
                'input'  => 'en;q=invalid',
                'actual' => 'en',
            ],
            'empty input'                 => [
                'input'  => ';,,',
                'actual' => '',
            ],
            'null input'                  => [
                'input'  => null,
                'actual' => '',
            ],
        ];
    }
}
