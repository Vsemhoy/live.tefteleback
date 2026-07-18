<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CtrDetail extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'ctr_details';

    protected $fillable = [
        'user_id',
        'contact_id',
        'kind',
        'label',
        'value',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CtrContact::class, 'contact_id');
    }
}
