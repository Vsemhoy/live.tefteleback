<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Symfony\Component\Uid\Ulid;

class EvtEmbed extends Model
{
    use HasFactory;

    protected $table = 'evt_embeds';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'event_id',
        'user_id',
        'url',
        'provider',
        'type',
        'title',
        'author',
        'thumbnail_url',
        'duration',
        'meta',
        'order',
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $keyName = $model->getKeyName();
            if (empty($model->{$keyName})) {
                $model->{$keyName} = (string) Ulid::generate();
            }
        });
    }

    // Связь с постом
    public function event()
    {
        return $this->belongsTo(EvtEvent::class, 'event_id');
    }

    // Связь с пользователем
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}