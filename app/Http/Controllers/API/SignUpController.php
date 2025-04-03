<?php

namespace App\Http\Controllers\API;

use App\Actions\CreateSignUpAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\CreateSignUpRequest;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SignUpController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(CreateSignUpRequest $request, CreateSignUpAction $signup): JsonResponse
    {
        $signup->handle($request);

        return new JsonResponse(
            data  : [
                'message' => $this->translator->get('response.user.created'),
            ],
            status: Response::HTTP_CREATED
        );
    }
}
