<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Uid\Ulid;

class EvtType extends Model
{
    protected $table = 'evt_types'; // Укажите имя таблицы, если оно не соответствует имени модели
    protected $primaryKey = 'id'; // Укажите первичный ключ
    public $incrementing = false; // Отключите автоинкремент
    protected $keyType = 'string'; // Установите тип ключа как string
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'color',
        'bgcolor',
        'sort_order',
        'icon',
        'is_archived',
        'is_default',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->hasMany(EvtEvent::class, 'type_id');
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Генерация уникального ID перед созданием записи
            $keyName = $model->getKeyName();
            $model->{$keyName} = (string) Ulid::generate();
        });
    }


}