<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AdminFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int             $id
 * @property string          $name
 * @property string          $email
 * @property string          $password
 * @property string|null     $remember_token
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
class Admin extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<AdminFactory> */
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
    ];

    /**
     * Determine if the user can access the given panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * Get the Admin User responsible for support issues.
     */
    public static function support(): ?self
    {
        return self::query()
            ->where('email', '=', config('mail.from.address'))
            ->first();
    }
}
