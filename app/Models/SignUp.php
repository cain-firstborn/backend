<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\SignUpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int             $id
 * @property int             $user_id
 * @property CarbonInterface $created_at
 */
class SignUp extends Model
{
    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = null;

    /** @use HasFactory<SignUpFactory> */
    use HasFactory;

    /**
     * User that owns the Sign-Up.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
