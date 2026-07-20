<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TskBlocker extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'tsk_blockers';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'occurrence_count',
    ];

    protected $casts = [
        'occurrence_count' => 'integer',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(TskLog::class, 'blocker_id');
    }
}