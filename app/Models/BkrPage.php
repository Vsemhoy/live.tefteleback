<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BkrPage extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'bkr_pages';

    protected $fillable = [
        'user_id',
        'book_id',
        'parent_id',
        'title',
        'slug',
        'visibility',
        'sort_order',
        'is_archived',
        'meta',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_archived' => 'boolean',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(BkrBook::class, 'book_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BkrPage::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BkrPage::class, 'parent_id')->orderBy('sort_order')->orderBy('title');
    }

    public function blockGroups(): HasMany
    {
        return $this->hasMany(BkrBlockGroup::class, 'page_id')->orderBy('sort_order')->orderBy('created_at');
    }
}
