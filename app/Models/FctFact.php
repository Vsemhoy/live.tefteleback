<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FctFact extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'fct_facts';

    protected $fillable = [
        'user_id',
        'label',
        'value',
        'format',
        'language',
        'unit',
        'context',
        'search_keywords',
        'kind',
        'display_mode',
        'is_sensitive',
        'is_expert',
        'valid_from',
        'valid_to',
        'is_pinned',
        'sort_order',
    ];

    protected $casts = [
        'search_keywords' => 'array',
        'is_sensitive' => 'boolean',
        'is_expert' => 'boolean',
        'is_pinned' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}