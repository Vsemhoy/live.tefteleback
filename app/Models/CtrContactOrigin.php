<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CtrContactOrigin extends Model
{
    use HasUlids;

    protected $table = 'ctr_contact_origins';

    protected $fillable = [
        'user_id',
        'contact_id',
        'origin_type',
        'origin_id',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CtrContact::class, 'contact_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}