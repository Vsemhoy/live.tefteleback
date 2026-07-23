<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CtrContact extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'ctr_contacts';

    protected $fillable = [
        'user_id',
        'name',
        'nickname',
        'group',
        'role',
        'company',
        'avatar',
        'avatar_url',
        'met_at',
        'met_precision',
        'met_context',
        'birthday_on',
        'birthday_precision',
        'last_contact_at',
        'details',
        'is_pinned',
        'sort_order',
        'is_archived',
    ];

    protected $casts = [
        'met_at' => 'date',
        'birthday_on' => 'date',
        'last_contact_at' => 'datetime',
        'details' => 'array',
        'is_pinned' => 'boolean',
        'sort_order' => 'integer',
        'is_archived' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detailsRows(): HasMany
    {
        return $this->hasMany(CtrDetail::class, 'contact_id')
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(CtrContent::class, 'contact_id')
            ->orderByDesc('is_pinned')
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');
    }

    public function origins(): HasMany
    {
        return $this->hasMany(CtrContactOrigin::class, 'contact_id')
            ->orderByDesc('created_at');
    }

    public function eventorEvents()
    {
        return $this->belongsToMany(EvtEvent::class, 'evt_event_contacts', 'contact_id', 'event_id')
            ->withPivot(['id', 'role', 'note', 'sort_order'])
            ->withTimestamps()
            ->orderByDesc('evt_events.occurred_at');
    }

    public function mentionedContents()
    {
        return $this->belongsToMany(CtrContent::class, 'ctr_content_mentions', 'contact_id', 'content_id')
            ->withPivot(['id', 'role', 'sort_order'])
            ->withTimestamps()
            ->orderByDesc('ctr_contents.occurred_at');
    }

    public function relationsA(): HasMany
    {
        return $this->hasMany(CtrRelation::class, 'contact_a_id');
    }

    public function relationsB(): HasMany
    {
        return $this->hasMany(CtrRelation::class, 'contact_b_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }
}
