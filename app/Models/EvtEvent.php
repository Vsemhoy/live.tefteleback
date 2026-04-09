<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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

    // public function pinAlgorithms()
    // {
    //     return $this->belongsToMany(PinAlgorithm::class, 'evt_pin_algorithm');
    // }

    public function starredByUser()
    {
        return $this->hasOne(EvtStarred::class, 'event_id')
                    ->where('user_id', auth()->id());
    }

    public function media()
    {
        return $this->hasMany(EvtMedia::class, 'event_id')->orderBy('sort_order');
    }

    public function embeds()
    {
        return $this->hasMany(EvtEmbed::class, 'event_id')->orderBy('order');
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
