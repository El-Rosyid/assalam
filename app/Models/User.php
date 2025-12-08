<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Panel;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',        
        'email',
        'password',
        'name',
        'avatar', // atau 'profile_photo_path'
    ];

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
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function siswa()
    {
        return $this->hasOne(data_siswa::class, 'user_id', 'user_id');
    }

    public function guru()
    {
        return $this->hasOne(data_guru::class, 'user_id', 'user_id');
    }
public function isSiswa(): bool
{
    return $this->hasRole('siswa');
}

public function isGuru(): bool
{
    return $this->hasRole('guru');
}

public function isAdmin(): bool
{
    return $this->hasRole('admin');
}

/**
 * Determine whether the user can access the Filament admin panel.
 *
 * Allow access for users with 'admin' or 'guru' roles.
 *
 * @param  \Filament\Panel  $panel
 * @return bool
 */
public function canAccessPanel(Panel $panel): bool
{
    return $this->hasAnyRole(['admin', 'super_admin', 'guru', 'siswa']);
}

/**
 * Get the user's avatar URL for Filament header.
 *
 * @return string|null
 */
public function getFilamentAvatarUrl(): ?string
{
    if ($this->avatar) {
        // Check if file exists in storage
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($this->avatar)) {
            return asset('storage/' . $this->avatar);
        }
    }
    
    // Return null to use default Filament avatar (circular with initials)
    return null;
}

}
