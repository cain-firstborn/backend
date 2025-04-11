<?php

namespace App\Actions;

use App\Http\Requests\API\CreateContactRequest;
use App\Models\Admin;
use App\Models\User;
use App\Notifications\ContactSubmitted;

class CreateContactAction extends Action
{
    /**
     * Execute the action.
     */
    public function handle(CreateContactRequest $request): void
    {
        $user = $this->cache
            ->rememberForever(
                key: "user:$request->email",
                callback: fn(): User => User::query()->firstOrCreate($request->only('email'))
            );

        $message = $user->messages()->create($request->only('name', 'message'));

        Admin::support()->notify(new ContactSubmitted($message));
    }
}
