<?php

namespace Exceptions;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;
use Tests\Traits\WithTranslator;
use Throwable;

class ThrottleRequestsTest extends TestCase
{
    use WithTranslator;

    /**
     * Test Exception Handler instance.
     */
    private Handler $handler;

    /**
     * Test Request instance.
     */
    private Request $request;

    /**
     * Set up the test environment.
     *
     * @throws BindingResolutionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->app->make(Handler::class);
        $this->request = $this->app->make(Request::class);
    }

    #[Test]
    public function it_throws_throttle_exception(): void
    {
        try {
            $this->handler->render($this->request->create('/test'), new ThrottleRequestsException());
        } catch (Throwable|TooManyRequestsHttpException $throwable) {
            $this->assertEquals(Response::HTTP_TOO_MANY_REQUESTS, $throwable->getStatusCode());
        }
    }

    #[Test]
    #[DataProvider('scenarios')]
    public function it_localizes_the_message(string $locale): void
    {
        $this->app->setLocale($locale);

        try {
            $this->handler->render($this->request->create('/test'), new ThrottleRequestsException());
        } catch (Throwable|TooManyRequestsHttpException $throwable) {
            $this->assertEquals($this->translator->get('exception.http.429'), $throwable->getMessage());
        }
    }

    /**
     * Set of locale scenarios.
     */
    public static function scenarios(): array
    {
        return [
            'english' => ['en'],
            'german'  => ['de'],
            'french'  => ['fr'],
            'italian' => ['it'],
        ];
    }
}
