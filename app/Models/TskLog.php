<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TskLog extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'tsk_logs';

    protected $fillable = [
        'user_id',
        'task_id',
        'kind',
        'content',
        'blocker_id',
        'timer_entry_id',
        'occurred_at',
        'meta',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'meta' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TskTask::class, 'task_id');
    }

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(TskBlocker::class, 'blocker_id');
    }

    public function timerEntry(): BelongsTo
    {
        return $this->belongsTo(SysTimerEntry::class, 'timer_entry_id');
    }
}