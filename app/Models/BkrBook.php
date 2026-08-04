<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BkrBook extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'bkr_books';

    protected $fillable = [
        'user_id',
        'space_id',
        'title',
        'slug',
        'description',
        'structure_mode',
        'visibility',
        'cover_color',
        'export_settings',
        'sort_order',
        'is_archived',
        'meta',
    ];

    protected $casts = [
        'export_settings' => 'array',
        'sort_order' => 'integer',
        'is_archived' => 'boolean',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(BkrSpace::class, 'space_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(BkrPage::class, 'book_id')->orderBy('sort_order')->orderBy('title');
    }
}
