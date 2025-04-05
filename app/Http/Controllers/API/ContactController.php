<?php

namespace App\Http\Controllers\API;

use App\Actions\CreateContactAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\CreateContactRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            ThrottleRequests::with(5, 1),
        ];
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(CreateContactRequest $request, CreateContactAction $contact): JsonResponse
    {
        $contact->handle($request);

        return new JsonResponse(
            data  : [
                'message' => $this->translator->get('response.contact.submitted'),
            ],
            status: Response::HTTP_CREATED
        );
    }
}
