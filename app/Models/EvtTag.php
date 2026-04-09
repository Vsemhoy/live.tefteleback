<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\Ulid;

class EvtTag extends Model
{
    protected $table = 'evt_tags';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';

    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'slug',
        'color',
        'bgcolor',
        'is_system',
        'sort_order',
        'is_archived',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function events()
    {
        return $this->belongsToMany(EvtEvent::class, 'evt_event_tags', 'tag_id', 'event_id');
    }
}
