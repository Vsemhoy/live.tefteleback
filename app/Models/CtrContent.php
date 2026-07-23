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
        'is_expert',
        'eventor_event_id',
        'stuffer_register_id',
        'exploiter_event_id',
        'tasker_task_id',
        'meta',
        'sort_order',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_expert' => 'boolean',
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

    public function mentionLinks()
    {
        return $this->hasMany(CtrContentMention::class, 'content_id')
            ->with('contact:id,name,nickname,avatar,avatar_url')
            ->orderBy('sort_order')
            ->orderBy('created_at');
    }

    public function mentionedContacts()
    {
        return $this->belongsToMany(CtrContact::class, 'ctr_content_mentions', 'content_id', 'contact_id')
            ->withPivot(['id', 'role', 'sort_order'])
            ->withTimestamps()
            ->orderBy('ctr_content_mentions.sort_order');
    }

    public function tags()
    {
        return $this->belongsToMany(EvtTag::class, 'ctr_content_tags', 'content_id', 'tag_id')
            ->select(['id', 'name', 'slug', 'color', 'bgcolor'])
            ->withTimestamps();
    }

    public function taskerTask(): BelongsTo
    {
        return $this->belongsTo(TskTask::class, 'tasker_task_id');
    }
}
