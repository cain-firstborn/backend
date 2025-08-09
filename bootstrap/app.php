<?php

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\ValidationMode;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web      : __DIR__ . '/../routes/web.php',
        api      : __DIR__ . '/../routes/api.php',
        apiPrefix: '',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(
            prepend: [
                SetLocale::class,
            ]
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->header('X-Validation-Mode') !== 'minimal') {
                return null;
            }

            $errors = [];

            foreach ($exception->validator->failed() as $field => $rules) {
                foreach ($rules as $rule => $parameters) {
                    $name = strtolower($rule);

                    $errors[$field][] = !empty($parameters)
                        ? $name . ':' . implode(',', $parameters)
                        : $name;
                }
            }

            throw new HttpResponseException(
                response: new JsonResponse(
                    data  : [
                        'message' => trans('exception.http.422'),
                        'errors'  => $errors,
                    ],
                    status: $exception->status
                ),
                previous: $exception->getPrevious(),
            );
        });

        $exceptions->render(function (ThrottleRequestsException $exception) {
            throw new TooManyRequestsHttpException(
                message : trans('exception.http.429'),
                previous: $exception->getPrevious(),
                code    : $exception->getStatusCode(),
                headers : $exception->getHeaders()
            );
        });
    })
    ->create();
