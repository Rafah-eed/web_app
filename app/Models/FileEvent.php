<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FileEvent extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'file_id',
        'event_type_id',
        'user_id',
        'date',
        'details',
    ];

    protected array $searchableFields = ['*'];

    protected $table = 'file_events';

    protected $casts = [
        'date' => 'date',
    ];

    public function file(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function eventType(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(EventType::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
