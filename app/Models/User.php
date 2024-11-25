<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;
    use Searchable;
    use HasApiTokens;
    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'password',
        'role_type',
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
    ];

    
    public function role(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function groups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    public function groupMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function files(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(File::class);
    }

    public function fileEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FileEvent::class);
    }

//    public function isSuperAdmin(): bool
//    {
//        return in_array($this->email, config('auth.super_admins'));
//    }

    public function fileUserReserved(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FileUserReserved::class);
    }
    public function requestUserToGroups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RequestUserToGroups::class);
    }
}
