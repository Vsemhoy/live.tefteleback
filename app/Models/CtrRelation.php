<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CtrRelation extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'ctr_relations';

    protected $fillable = [
        'user_id',
        'contact_a_id',
        'contact_b_id',
        'kind',
        'context',
        'valid_from',
        'valid_to',
        'note',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contactA(): BelongsTo
    {
        return $this->belongsTo(CtrContact::class, 'contact_a_id');
    }

    public function contactB(): BelongsTo
    {
        return $this->belongsTo(CtrContact::class, 'contact_b_id');
    }
}