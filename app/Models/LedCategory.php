<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LedCategory extends Model
{
    protected $table = 'led_categories';

    protected $fillable = [
        'id', 'user_id', 'parent_id',
        'name', 'depth', 'path', 'sort_order', 'is_archived',
    ];

    protected $casts = [
        'depth' => 'integer',
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

    public function children(): HasMany
    {
        return $this->hasMany(LedCategory::class, 'parent_id', 'id')->orderBy('sort_order');
    }

    public function parent(): ?self
    {
        return $this->belongsTo(LedCategory::class, 'parent_id', 'id');
    }
}
