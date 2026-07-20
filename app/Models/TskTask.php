<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TskTask extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'tsk_tasks';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'result',
        'assignee_contact_id',
        'priority_id',
        'status_id',
        'due_at',
        'eventor_event_id',
        'parent_task_id',
        'project_id',
        'tracked_seconds',
        'sort_order',
        'is_pinned',
        'is_expert',
        'is_hidden',
        'closed_at',
    ];

    protected $casts = [
        'priority_id' => 'integer',
        'status_id' => 'integer',
        'due_at' => 'date',
        'tracked_seconds' => 'integer',
        'sort_order' => 'integer',
        'is_pinned' => 'boolean',
        'is_expert' => 'boolean',
        'is_hidden' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function assigneeContact(): BelongsTo
    {
        return $this->belongsTo(CtrContact::class, 'assignee_contact_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(PrjProject::class, 'project_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_task_id')->orderBy('sort_order')->orderBy('created_at');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TskLog::class, 'task_id')->orderByDesc('occurred_at')->orderByDesc('created_at');
    }

    public function timerEntries(): HasMany
    {
        return $this->hasMany(SysTimerEntry::class, 'source_id')
            ->where('source_module', 'tasker')
            ->orderByDesc('started_at');
    }
}