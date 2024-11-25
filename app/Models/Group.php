<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'owner_id'];

    protected array $searchableFields = ['*'];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function groupMembers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    public function files(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(File::class);
    }

    public function fileUserReserved(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FileUserReserved::class);
    }
    public function requestUserToGroups(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RequestUserToGroups::class);
    }
}
