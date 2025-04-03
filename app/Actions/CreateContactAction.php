<?php

namespace App\Actions;

use App\Http\Requests\API\CreateContactRequest;
use App\Models\User;
use App\Notifications\ContactSubmitted;
use Illuminate\Support\Facades\Notification;

class CreateContactAction extends Action
{
    /**
     * Execute the action.
     */
    public function handle(CreateContactRequest $request): void
    {
        if ($this->cache->has("contact_cooldown:$request->email")) {
            return;
        }

        $user = $this->cache
            ->rememberForever(
                key     : "user:$request->email",
                callback: fn(): User => User::query()->firstOrCreate($request->only('email'))
            );

        $user->messages()->create($request->only('name', 'message'));

        Notification::route('mail', config('mail.from.address'))
            ->notify(new ContactSubmitted(...$request->validated()));

        $this->cache->put(
            key  : "contact_cooldown:$request->email",
            value: true,
            ttl  : now()->addDay()
        );
    }

}
