<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CntContent extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'cnt_contents';

    protected $fillable = [
        'user_id',
        'source_module',
        'source_id',
        'field',
        'kind',
        'title',
        'body_md',
        'body_hash',
        'locale',
        'status',
        'is_primary',
        'sort_order',
        'meta',
    ];

    protected $casts = [
        'status' => 'integer',
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForSource($query, string $module, string $sourceId)
    {
        return $query->where('source_module', $module)
            ->where('source_id', $sourceId);
    }

    public static function syncPrimaryMarkdown(
        string $userId,
        string $module,
        string $sourceId,
        ?string $body,
        string $field = 'content',
        ?string $title = null
    ): ?self {
        $body = is_string($body) ? trim($body) : '';

        $query = static::query()
            ->where('user_id', $userId)
            ->where('source_module', $module)
            ->where('source_id', $sourceId)
            ->where('field', $field)
            ->where('kind', 'markdown')
            ->where('is_primary', true);

        if ($body === '') {
            $query->delete();
            return null;
        }

        $content = $query->first() ?? new static([
            'user_id' => $userId,
            'source_module' => $module,
            'source_id' => $sourceId,
            'field' => $field,
            'kind' => 'markdown',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $content->fill([
            'title' => $title,
            'body_md' => $body,
            'body_hash' => hash('sha256', $body),
            'status' => 1,
        ]);
        $content->save();

        return $content;
    }
}
