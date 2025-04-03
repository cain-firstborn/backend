<?php

namespace App\Actions;

use App\Http\Requests\API\CreateSignUpRequest;
use App\Models\User;
use App\Notifications\UserSignedUp;

class CreateSignUpAction extends Action
{
    /**
     * Execute the action.
     */
    public function handle(CreateSignUpRequest $request): void
    {
        if ($this->cache->has("signup_cooldown:$request->email")) {
            return;
        }

        $user = $this->cache
            ->rememberForever(
                key     : "user:$request->email",
                callback: fn(): User => User::query()->firstOrCreate($request->only('email'))
            );

        $user->signings()->create();

        $user->notify(new UserSignedUp());

        $this->cache->put(
            key  : "signup_cooldown:$request->email",
            value: true,
            ttl  : now()->addDay()
        );
    }

}
