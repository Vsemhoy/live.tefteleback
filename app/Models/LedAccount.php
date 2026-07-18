<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LedAccount extends Model
{
    protected $table = 'led_accounts';

    protected $fillable = [
        'user_id',
        'layer_id',
        'name',
        'literals',
        'type',
        'currency',
        'opening_balance',
        'color',
        'sort_order',
        'is_archived',
        'is_expert',
        'opened_at',
        'closed_at',
        'interest_rate',
        'interest_start',
    ];

    protected $casts = [
        'opening_balance' => 'integer',
        'sort_order'      => 'integer',
        'is_archived'     => 'boolean',
        'is_expert'       => 'boolean',
        'interest_rate'   => 'integer',
        'opened_at'       => 'date:Y-m-d',
        'closed_at'       => 'date:Y-m-d',
        'interest_start'  => 'date:Y-m-d',
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

    public function getIncrementing() { return false; }
    public function getKeyType()      { return 'string'; }

    public function transactions(): HasMany
    {
        return $this->hasMany(LedTransaction::class, 'account_id');
    }
}
