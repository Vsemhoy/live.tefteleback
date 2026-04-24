<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class StfLocation extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'stf_locations';

    protected $fillable = [
        'user_id',
        'name',
        'parent_id',
        'sort_order',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function things()
    {
        return $this->hasMany(StfThing::class, 'current_location_id');
    }

    public function canForceDelete(): bool
    {
        return DB::table('stf_register')
            ->where('from_location_id', $this->id)
            ->orWhere('to_location_id', $this->id)
            ->doesntExist();
    }
}
