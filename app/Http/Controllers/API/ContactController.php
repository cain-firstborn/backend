<?php

namespace App\Http\Controllers\API;

use App\Actions\CreateContactAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\CreateContactRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends Controller
{
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
