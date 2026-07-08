<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\Ulid;

class EvtEvent extends Model
{
    protected $table = 'evt_events'; // Укажите имя таблицы, если оно не соответствует имени модели

    protected $primaryKey = 'id'; // Укажите первичный ключ

    public $incrementing = false; // Отключите автоинкремент

    protected $keyType = 'string'; // Установите тип ключа как string

    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type_id',
        'section_id',
        'category_id',
        'project_id',
        'exploiter_event_id',
        'content',
        'format',
        'metadata',
        'language',
        'code_language',
        'location',
        'client',
        'status',
        'sort_order',
        'access',
        'comment_access',
        'is_locked',
        'is_pinned',
        'is_blurred',

        'root_id',
        'parent_id',
        'relation_type',

        'setdate',

    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        return $this->belongsTo(EvtType::class, 'type_id');
    }

    public function evt_type()
    {
        return $this->belongsTo(EvtType::class, 'type_id');
    }

    public function section()
    {
        return $this->belongsTo(EvtSection::class, 'section_id');
    }

    public function category()
    {
        return $this->belongsTo(EvtCategory::class, 'category_id');
    }

    public function exploiterEvent()
    {
        return $this->belongsTo(StfRegister::class, 'exploiter_event_id');
    }

    public function contentBlocks()
    {
        return $this->hasMany(CntContent::class, 'source_id')
            ->where('source_module', 'eventor')
            ->orderBy('sort_order');
    }

    public function primaryContent()
    {
        return $this->hasOne(CntContent::class, 'source_id')
            ->where('source_module', 'eventor')
            ->where('field', 'content')
            ->where('kind', 'markdown')
            ->where('is_primary', true);
    }

    public function tags()
    {
        return $this->belongsToMany(EvtTag::class, 'evt_event_tags', 'event_id', 'tag_id')
            ->select(['id', 'name', 'slug', 'color', 'bgcolor']);
    }

    // public function pinAlgorithms()
    // {
    //     return $this->belongsToMany(PinAlgorithm::class, 'evt_pin_algorithm');
    // }

    public function starredByUser()
    {
        return $this->hasOne(EvtStarred::class, 'event_id')
            ->where('user_id', auth()->id());
    }

    public function parent()
    {
        return $this->belongsTo(EvtEvent::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(EvtEvent::class, 'parent_id');
    }

    public function media()
    {
        return $this->hasMany(EvtMedia::class, 'event_id')->orderBy('sort_order');
    }

    public function embeds()
    {
        return $this->hasMany(EvtEmbed::class, 'event_id')->orderBy('order');
    }

    public function syncPrimaryContent(?string $body): ?CntContent
    {
        return CntContent::syncPrimaryMarkdown(
            $this->user_id,
            'eventor',
            $this->id,
            $body,
            'content',
            $this->name
        );
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Генерация уникального ID перед созданием записи
            // Генерация ID
            $keyName = $model->getKeyName();
            $model->{$keyName} = (string) Ulid::generate();

            // Устанавливаем root_id = id по умолчанию
            $model->root_id = $model->{$keyName};
        });

    }
}
