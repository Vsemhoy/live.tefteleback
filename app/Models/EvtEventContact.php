<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvtEventContact extends Model
{
    use HasUlids;

    protected $table = 'evt_event_contacts';

    protected $fillable = [
        'user_id',
        'event_id',
        'contact_id',
        'role',
        'note',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(EvtEvent::class, 'event_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CtrContact::class, 'contact_id');
    }
}
