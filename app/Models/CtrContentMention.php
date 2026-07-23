<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CtrContentMention extends Model
{
    use HasUlids;

    protected $table = 'ctr_content_mentions';

    protected $fillable = [
        'user_id',
        'content_id',
        'contact_id',
        'role',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(CtrContent::class, 'content_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CtrContact::class, 'contact_id');
    }
}
