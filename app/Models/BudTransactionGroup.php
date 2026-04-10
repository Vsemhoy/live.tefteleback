<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BudTransactionGroup extends Model
{
    protected $table = 'bud_transaction_groups';

    protected $fillable = [
        'user_id',
        'name',
        'is_disabled',
        'color',
    ];

    protected $casts = [
        'is_disabled' => 'boolean',
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
