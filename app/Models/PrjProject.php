<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrjProject extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'prj_projects';

    protected $fillable = [
        'user_id',
        'title',
        'code',
        'color',
        'description',
        'result',
        'priority_id',
        'status_id',
        'started_on',
        'due_at',
        'closed_at',
        'is_pinned',
        'is_expert',
        'is_hidden',
        'show_in_tasker',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'priority_id' => 'integer',
        'status_id' => 'integer',
        'started_on' => 'date',
        'due_at' => 'date',
        'closed_at' => 'datetime',
        'is_pinned' => 'boolean',
        'is_expert' => 'boolean',
        'is_hidden' => 'boolean',
        'show_in_tasker' => 'boolean',
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(TskTask::class, 'project_id')->orderBy('sort_order')->orderByDesc('updated_at');
    }
}