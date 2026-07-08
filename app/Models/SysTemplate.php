<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SysTemplate extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'sys_templates';

    protected $fillable = [
        'user_id',
        'module',
        'name',
        'icon',
        'payload',
        'schedule',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'payload' => 'array',
        'schedule' => 'array',
        'status' => 'integer',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
