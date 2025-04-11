<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

/**
 * @property int                 $id
 * @property string              $email
 * @property CarbonInterface     $created_at
 * @property CarbonInterface     $updated_at
 * @property Collection<SignUp>  $signups
 * @property Collection<Message> $messages
 */
class User extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * Sign-Ups that belongs to the User.
     */
    public function signups(): HasMany
    {
        return $this->hasMany(SignUp::class);
    }

    /**
     * Messages that belongs to the User.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
