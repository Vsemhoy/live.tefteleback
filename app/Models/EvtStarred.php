<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Symfony\Component\Uid\Ulid;

class EvtStarred extends Model
{
    use HasFactory;

    protected $table = 'evt_starred';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'event_id',
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

    // Связь с пользователем
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Связь с событием
    public function event()
    {
        return $this->belongsTo(EvtEvent::class, 'event_id');
    }
}