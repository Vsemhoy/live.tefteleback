<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BudTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'bud_transactions';

    protected $fillable = [
        'user_id',
        'layer_id',
        'account_id',
        'target_account_id',
        'group_id',
        'category_id',
        'original_transaction_id',
        'flow_kind',
        'amount',
        'occurred_at',
        'month_key',
        'title',
        'note',
        'status',
        'is_disabled',
        'is_pinned',
        'sort_order',
        'linked_entity_type',
        'linked_entity_id',
    ];

    protected $casts = [
        'amount' => 'integer',
        'sort_order' => 'integer',
        'is_disabled' => 'boolean',
        'is_pinned' => 'boolean',
        'occurred_at' => 'date:Y-m-d',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::ulid();
            }
        });
    }

    public function getIncrementing()
    {
        return false;
    }

    public function getKeyType()
    {
        return 'string';
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(EvtTag::class, 'bud_transaction_tags', 'transaction_id', 'tag_id');
    }

    public function category(): ?BelongsTo
    {
        return $this->belongsTo(BudCategory::class, 'category_id', 'id');
    }
}
