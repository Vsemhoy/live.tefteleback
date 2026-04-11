<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BudAccount extends Model
{
    protected $table = 'bud_accounts';

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
    ];

    protected $casts = [
        'opening_balance' => 'integer',
        'sort_order' => 'integer',
        'is_archived' => 'boolean',
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
}
