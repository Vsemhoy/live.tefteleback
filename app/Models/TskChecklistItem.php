<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TskChecklistItem extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'tsk_checklist_items';

    protected $fillable = [
        'user_id',
        'task_id',
        'title',
        'status_id',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'status_id' => 'integer',
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(TskTask::class, 'task_id');
    }
}
