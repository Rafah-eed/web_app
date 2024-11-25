<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventType extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'description'];

    protected array $searchableFields = ['*'];

    protected $table = 'event_types';

    public function fileEvents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FileEvent::class);
    }
}
