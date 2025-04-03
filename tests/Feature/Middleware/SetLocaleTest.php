<?php

namespace Tests\Feature\Middleware;

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SetLocaleTest extends TestCase
{
    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->setLocale('en');

        Route::middleware(SetLocale::class)->get('/locale', fn() => ['locale' => $this->app->getLocale()]);
    }

    #[Test]
    public function it_sets_locale_from_accept_language_header(): void
    {
        $this
            ->get(
                uri    : '/locale',
                headers: [
                    'Accept-Language' => 'de',
                ]
            )
            ->assertJson(['locale' => 'de']);

        $this->assertEquals('de', $this->app->getLocale());
    }

    #[Test]
    public function it_falls_back_to_default_locale_when_no_header_present(): void
    {
        $this->app->setLocale('fr');

        $this
            ->get(
                uri: '/locale',
            )
            ->assertJson(['locale' => 'fr']);

        $this->assertEquals('fr', $this->app->getLocale());
    }

    #[Test]
    public function it_uses_default_for_unsupported_languages(): void
    {
        config()->set('app.supported_locale', ['en', 'de']);

        $this
            ->get(
                uri    : '/locale',
                headers: [
                    'Accept-Language' => 'fr',
                ]
            )
            ->assertJson(['locale' => 'en']);
    }
}
