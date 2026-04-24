<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StfThing extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'stf_things';

    protected $fillable = [
        'user_id',
        'entity_type',
        'name',
        'description',
        'vendor',
        'url',
        'parent_id',
        'category_id',
        'current_location_id',
        'current_status',
        'serial_no',
        'qty',
        'unit',
        'purchase_price',
        'purchase_date',
        'open_count',
        'last_opened_at',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'purchase_date' => 'date',
        'last_opened_at' => 'datetime',
        'purchase_price' => 'integer',
        'open_count' => 'integer',
        'qty' => 'decimal:2',
    ];

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function location()
    {
        return $this->belongsTo(StfLocation::class, 'current_location_id')->withTrashed();
    }

    public function category()
    {
        return $this->belongsTo(BudCategory::class, 'category_id');
    }

    public function register()
    {
        return $this->hasMany(StfRegister::class, 'thing_id')
            ->orderByDesc('occurred_at')
            ->orderByDesc('created_at');
    }

    public function expenses()
    {
        return $this->hasMany(StfExpense::class, 'thing_id');
    }

    public function recordOpen(): void
    {
        $this->increment('open_count');
        $this->update(['last_opened_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeByRelevance($query)
    {
        return $query->orderByDesc('last_opened_at')
            ->orderByDesc('open_count');
    }
}
