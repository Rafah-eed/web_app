<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class File extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'name',
        'extension',
        'group_id',
        'user_id',
        'path',
        'is_active',
        'is_reserved',
    ];

    protected array $searchableFields = ['*'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_reserved' => 'boolean',
    ];

    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fileEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FileEvent::class);
    }


}
