<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CtrContent extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'ctr_contents';

    protected $fillable = [
        'user_id',
        'contact_id',
        'kind',
        'occurred_at',
        'title',
        'body_md',
        'is_pinned',
        'eventor_event_id',
        'stuffer_register_id',
        'exploiter_event_id',
        'meta',
        'sort_order',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'is_pinned' => 'boolean',
        'meta' => 'array',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CtrContact::class, 'contact_id');
    }
}