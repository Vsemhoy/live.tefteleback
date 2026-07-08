<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SysTimerEntry extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'sys_timer_entries';

    protected $fillable = [
        'user_id',
        'started_at',
        'ended_at',
        'duration_min',
        'entry_type',
        'time_type',
        'source_module',
        'source_id',
        'sort_order',
        'note',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_min' => 'integer',
        'sort_order' => 'integer',
    ];

    public function exploiterEvent()
    {
        return $this->belongsTo(StfRegister::class, 'source_id')
            ->where('source_module', 'exploiter');
    }

    public function contentBlocks()
    {
        return $this->hasMany(CntContent::class, 'source_id')
            ->where('source_module', 'timer.entry')
            ->orderBy('sort_order');
    }

    public function primaryContent()
    {
        return $this->hasOne(CntContent::class, 'source_id')
            ->where('source_module', 'timer.entry')
            ->where('field', 'content')
            ->where('kind', 'markdown')
            ->where('is_primary', true);
    }
}
