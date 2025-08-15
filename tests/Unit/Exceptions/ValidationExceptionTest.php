<?php

namespace Tests\Unit\Exceptions;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Exceptions\Handler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use Tests\Traits\WithTranslator;
use Throwable;

class ValidationExceptionTest extends TestCase
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
    public function it_returns_normal_validation_errors_without_header(): void
    {
        $request   = $this->request();
        $validator = $this->validator(rules: ['field' => ['required', 'field']]);

        try {
            $validator->validate();
        } catch (ValidationException $exception) {
            /** @var RedirectResponse $response */
            $response = $this->handler->render($request, $exception);

            $this->assertEquals(
                expected: Response::HTTP_FOUND,
                actual  : $response->getStatusCode()
            );

            $data = $response->getSession()
                ->get('errors')
                ->getMessages();

            $this->assertArrayHasKey('field', $data);
            $this->assertContains($this->translator->get('validation.required', ['attribute' => 'field']), $data['field']);
        }
    }

    #[Test]
    public function it_transforms_validation_errors_when_header_is_present(): void
    {
        $request   = $this->request(['server' => ['HTTP_X-Validation-Mode' => 'minimal']]);
        $validator = $this->validator(rules: ['field' => ['required', 'field']]);

        try {
            $validator->validate();
        } catch (ValidationException $exception) {
            try {
                $this->handler->render($request, $exception);
            } catch (Throwable|HttpResponseException $exception) {
                /** @var JsonResponse $response */
                $response = $exception->getResponse();

                $this->assertEquals(
                    expected: Response::HTTP_UNPROCESSABLE_ENTITY,
                    actual  : $response->getStatusCode()
                );

                $this->assertEquals(
                    expected: [
                        'message' => 'Invalid Data Given',
                        'errors'  => [
                            'field' => ['required'],
                        ],
                    ],
                    actual  : $response->getData(true)
                );
            }
        }
    }

    #[Test]
    public function it_properly_handles_rules_with_multiple_parameters(): void
    {
        $request   = $this->request(['server' => ['HTTP_X-Validation-Mode' => 'minimal']]);
        $validator = $this->validator(['field' => '1'], ['field' => ['between:2,4']]);

        try {
            $validator->validate();
        } catch (ValidationException $exception) {
            try {
                $this->handler->render($request, $exception);
            } catch (Throwable|HttpResponseException $exception) {
                /** @var JsonResponse $response */
                $response = $exception->getResponse();

                $this->assertEquals(
                    expected: Response::HTTP_UNPROCESSABLE_ENTITY,
                    actual  : $response->getStatusCode()
                );

                $this->assertEquals(
                    expected: [
                        'message' => 'Invalid Data Given',
                        'errors'  => [
                            'field' => ['between:2,4'],
                        ],
                    ],
                    actual  : $response->getData(true)
                );
            }
        }
    }

    #[Test]
    #[DataProvider('scenarios')]
    public function it_localizes_the_message(string $locale): void
    {
        $this->app->setLocale($locale);

        $request   = $this->request(['server' => ['HTTP_X-Validation-Mode' => 'minimal']]);
        $validator = $this->validator(rules: ['field' => ['required']]);

        try {
            $validator->validate();
        } catch (ValidationException $exception) {
            try {
                $this->handler->render($request, $exception);
            } catch (Throwable|HttpResponseException $exception) {
                /** @var JsonResponse $response */
                $response = $exception->getResponse();

                $this->assertEquals(
                    expected: Response::HTTP_UNPROCESSABLE_ENTITY,
                    actual  : $response->getStatusCode()
                );

                $this->assertEquals(
                    expected: [
                        'message' => $this->translator->get('exception.http.422'),
                        'errors'  => [
                            'field' => ['required'],
                        ],
                    ],
                    actual  : $response->getData(true)
                );
            }
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

    /**
     * Create a Request instance for the given data.
     *
     * @param array $data
     *
     * @return Request
     */
    private function request(array $data = []): Request
    {
        $data = array_merge($data, [
            'uri'    => '/test',
            'method' => 'GET',
        ]);

        return $this->request->create(...$data);
    }

    /**
     * Create a Validator instance for the given data.
     *
     * @param array $data
     * @param array $rules
     *
     * @return Validator
     */
    private function validator(array $data = [], array $rules = []): Validator
    {
        return $this->app
            ->make(ValidationFactory::class)
            ->make($data, $rules);
    }
}
