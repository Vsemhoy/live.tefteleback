<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class StfRegister extends Model
{
    use HasUlids;

    protected $table = 'stf_register';

    protected $fillable = [
        'user_id',
        'thing_id',
        'event_type',
        'event_kind',
        'from_location_id',
        'to_location_id',
        'contact',
        'return_expected',
        'amount',
        'note',
        'details',
        'status',
        'priority',
        'is_pinned',
        'is_expert',
        'part_cost',
        'labor_cost',
        'time_self_min',
        'time_service_min',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'date',
        'return_expected' => 'date',
        'amount' => 'integer',
        'details' => 'array',
        'event_kind' => 'string',
        'status' => 'integer',
        'priority' => 'integer',
        'is_pinned' => 'boolean',
        'is_expert' => 'boolean',
        'part_cost' => 'integer',
        'labor_cost' => 'integer',
        'time_self_min' => 'integer',
        'time_service_min' => 'integer',
    ];

    public function thing()
    {
        return $this->belongsTo(StfThing::class, 'thing_id');
    }

    public function fromLocation()
    {
        return $this->belongsTo(StfLocation::class, 'from_location_id')->withTrashed();
    }

    public function toLocation()
    {
        return $this->belongsTo(StfLocation::class, 'to_location_id')->withTrashed();
    }

    public function expense()
    {
        return $this->hasOne(StfExpense::class, 'register_id');
    }

    public function ledgerTransactions()
    {
        return $this->hasMany(LedTransaction::class, 'exploiter_event_id')
            ->orderBy('sort_order');
    }

    public function timerEntries()
    {
        return $this->hasMany(SysTimerEntry::class, 'source_id')
            ->where('source_module', 'exploiter')
            ->orderBy('sort_order');
    }

    public function eventorEvents()
    {
        return $this->hasMany(EvtEvent::class, 'exploiter_event_id');
    }

    public function contentBlocks()
    {
        return $this->hasMany(CntContent::class, 'source_id')
            ->where('source_module', 'exploiter')
            ->orderBy('sort_order');
    }

    public function primaryContent()
    {
        return $this->hasOne(CntContent::class, 'source_id')
            ->where('source_module', 'exploiter')
            ->where('field', 'content')
            ->where('kind', 'markdown')
            ->where('is_primary', true);
    }

    public static function statusFromEvent(string $eventType): string
    {
        return match ($eventType) {
            'bought', 'received', 'returned', 'noted' => 'active',
            'ordered' => 'ordered',
            'moved' => 'active',
            'installed' => 'installed',
            'lent' => 'lent',
            'sold' => 'sold',
            'lost', 'stolen' => 'lost',
            'disposed' => 'disposed',
            'repaired' => 'stored',
            default => 'active',
        };
    }
}

