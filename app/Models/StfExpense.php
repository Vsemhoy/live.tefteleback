<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class StfExpense extends Model
{
    use HasUlids;

    protected $table = 'stf_expenses';

    protected $fillable = [
        'user_id',
        'thing_id',
        'register_id',
        'transaction_id',
        'amount',
        'note',
        'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'date',
        'amount' => 'integer',
    ];

    public function thing()
    {
        return $this->belongsTo(StfThing::class, 'thing_id');
    }

    public function register()
    {
        return $this->belongsTo(StfRegister::class, 'register_id');
    }

    public function transaction()
    {
        return $this->belongsTo(LedTransaction::class, 'transaction_id');
    }
}
