<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BkrBlockGroup extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'bkr_block_groups';

    protected $fillable = [
        'user_id',
        'page_id',
        'master_block_id',
        'type',
        'role',
        'visibility',
        'is_hidden_by_default',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'is_hidden_by_default' => 'boolean',
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(BkrPage::class, 'page_id');
    }

    public function masterBlock(): BelongsTo
    {
        return $this->belongsTo(BkrBlock::class, 'master_block_id');
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(BkrBlock::class, 'group_id')->orderByDesc('version_number');
    }
}
