<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BkrSpace extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'bkr_spaces';

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'visibility',
        'sort_order',
        'is_archived',
        'meta',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_archived' => 'boolean',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function books(): HasMany
    {
        return $this->hasMany(BkrBook::class, 'space_id')->orderBy('sort_order')->orderBy('title');
    }
}
