<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LedTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'led_transactions';

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
        'exploiter_event_id',
        'cost_type',
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
        return $this->belongsToMany(EvtTag::class, 'led_transaction_tags', 'transaction_id', 'tag_id');
    }

    public function category(): ?BelongsTo
    {
        return $this->belongsTo(LedCategory::class, 'category_id', 'id');
    }

    public function exploiterEvent(): ?BelongsTo
    {
        return $this->belongsTo(StfRegister::class, 'exploiter_event_id', 'id');
    }

    public function contentBlocks()
    {
        return $this->hasMany(CntContent::class, 'source_id')
            ->where('source_module', 'ledger.transaction')
            ->orderBy('sort_order');
    }

    public function primaryContent()
    {
        return $this->hasOne(CntContent::class, 'source_id')
            ->where('source_module', 'ledger.transaction')
            ->where('field', 'content')
            ->where('kind', 'markdown')
            ->where('is_primary', true);
    }
}
