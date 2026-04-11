<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BudMonthTotal extends Model
{
    protected $table = 'bud_month_totals';

    public $timestamps = ['updated_at'];

    protected $fillable = [
        'user_id',
        'layer_id',
        'account_id',
        'month_key',
        'opening_balance',
        'closing_balance',
        'income_total',
        'expense_total',
        'transfer_in_total',
        'transfer_out_total',
        'adjustment_total',
        'tx_count',
        'is_dirty',
    ];

    protected $casts = [
        'opening_balance' => 'integer',
        'closing_balance' => 'integer',
        'income_total' => 'integer',
        'expense_total' => 'integer',
        'transfer_in_total' => 'integer',
        'transfer_out_total' => 'integer',
        'adjustment_total' => 'integer',
        'tx_count' => 'integer',
        'is_dirty' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::ulid();
            }
        });
        static::updating(function ($model) {
            $model->updated_at = now();
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
}
