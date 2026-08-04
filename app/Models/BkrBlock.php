<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BkrBlock extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'bkr_blocks';

    protected $fillable = [
        'user_id',
        'group_id',
        'version_number',
        'title',
        'content',
        'payload',
        'status',
        'published_at',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'payload' => 'array',
        'published_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(BkrBlockGroup::class, 'group_id');
    }
}
