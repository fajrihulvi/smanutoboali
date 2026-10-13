<?php

namespace App\Models;

use App\Modules\Auth\Notifications\ResetPasswordNotification;
use App\Modules\Core\Enums\Role;
use App\Support\Concerns\HasPublicId;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $email
 * @property bool $is_active
 * @property bool $must_change_password
 * @property CarbonImmutable|null $email_verified_at
 * @property CarbonImmutable|null $last_login_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPublicId, HasRoles, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'id',
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Password diganti (lewat profil, reset password, atau operator) → kewajiban ganti password selesai.
     */
    protected static function booted(): void
    {
        static::updating(function (User $user) {
            if ($user->isDirty('password') && ! $user->isDirty('must_change_password')) {
                $user->must_change_password = false;
            }
        });
    }

    public function hasRoleEnum(Role $role): bool
    {
        return $this->hasRole($role->value);
    }

    /**
     * Role inti yang dimiliki user, urut sesuai deklarasi enum.
     *
     * @return list<Role>
     */
    public function coreRoles(): array
    {
        $names = $this->getRoleNames();

        return array_values(array_filter(
            Role::cases(),
            fn (Role $role) => $names->contains($role->value),
        ));
    }

    /**
     * Kirim email reset password berbahasa Indonesia.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Channel privat notifikasi realtime memakai public_id (tidak mengekspos id numerik).
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'App.Models.User.'.$this->public_id;
    }
}
