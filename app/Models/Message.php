<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int             $id
 * @property int             $user_id
 * @property string          $email
 * @property string          $name
 * @property string          $message
 * @property CarbonInterface $created_at
 */
class Message extends Model
{
    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = null;

    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    /**
     * User that owns the Message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
