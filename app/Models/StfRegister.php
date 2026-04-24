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
        'from_location_id',
        'to_location_id',
        'contact',
        'return_expected',
        'amount',
        'note',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'date',
        'return_expected' => 'date',
        'amount' => 'integer',
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
