<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TskSpan extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'tsk_spans';

    protected $fillable = [
        'user_id',
        'task_id',
        'kind',
        'title',
        'content',
        'planned_start_at',
        'planned_end_at',
        'started_at',
        'ended_at',
        'auto_stop_at',
        'auto_stopped_at',
        'auto_stop_reason',
        'sort_order',
    ];

    protected $casts = [
        'planned_start_at' => 'datetime',
        'planned_end_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'auto_stop_at' => 'datetime',
        'auto_stopped_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TskTask::class, 'task_id');
    }
}
