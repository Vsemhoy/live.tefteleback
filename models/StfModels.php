<?php
// ══════════════════════════════════════════════════════════════════
// StfLocation.php
// ══════════════════════════════════════════════════════════════════
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class StfLocation extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'stf_locations';

    protected $fillable = [
        'user_id', 'name', 'parent_id', 'sort_order', 'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    // Дочерние локации
    public function children()
    {
        return $this->hasMany(StfLocation::class, 'parent_id')
                    ->orderBy('sort_order');
    }

    // Родительская локация
    public function parent()
    {
        return $this->belongsTo(StfLocation::class, 'parent_id');
    }

    // Вещи в этой локации
    public function things()
    {
        return $this->hasMany(StfThing::class, 'current_location_id');
    }

    // Можно ли жёстко удалить?
    // Только если в stf_register нет ни одной ссылки
    public function canForceDelete(): bool
    {
        return \DB::table('stf_register')
            ->where('from_location_id', $this->id)
            ->orWhere('to_location_id', $this->id)
            ->doesntExist();
    }
}


// ══════════════════════════════════════════════════════════════════
// StfThing.php
// ══════════════════════════════════════════════════════════════════
class StfThing extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'stf_things';

    protected $fillable = [
        'user_id', 'entity_type', 'name', 'description',
        'vendor', 'url', 'parent_id', 'category_id',
        'current_location_id', 'current_status',
        'serial_no', 'qty', 'unit',
        'purchase_price', 'purchase_date',
        'open_count', 'last_opened_at', 'is_archived',
    ];

    protected $casts = [
        'is_archived'    => 'boolean',
        'purchase_date'  => 'date',
        'last_opened_at' => 'datetime',
        'purchase_price' => 'integer',
        'open_count'     => 'integer',
        'qty'            => 'decimal:2',
    ];

    // Дочерние вещи (компоненты/Items относящиеся к этому Asset)
    public function children()
    {
        return $this->hasMany(StfThing::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(StfThing::class, 'parent_id');
    }

    public function location()
    {
        return $this->belongsTo(StfLocation::class, 'current_location_id')
                    ->withTrashed(); // показываем даже удалённые локации в истории
    }

    public function category()
    {
        return $this->belongsTo(BudCategory::class, 'category_id');
    }

    // История событий
    public function register()
    {
        return $this->hasMany(StfRegister::class, 'thing_id')
                    ->orderByDesc('occurred_at')
                    ->orderByDesc('created_at');
    }

    // Расходы
    public function expenses()
    {
        return $this->hasMany(StfExpense::class, 'thing_id');
    }

    // Инкремент счётчика открытий
    public function recordOpen(): void
    {
        $this->increment('open_count');
        $this->update(['last_opened_at' => now()]);
    }

    // Скоуп: только не архивированные
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    // Скоуп: сортировка по частоте использования
    public function scopeByRelevance($query)
    {
        return $query->orderByDesc('last_opened_at')
                     ->orderByDesc('open_count');
    }
}


// ══════════════════════════════════════════════════════════════════
// StfRegister.php
// ══════════════════════════════════════════════════════════════════
class StfRegister extends Model
{
    use HasUlids;

    protected $table = 'stf_register';

    protected $fillable = [
        'user_id', 'thing_id', 'event_type',
        'from_location_id', 'to_location_id',
        'contact', 'return_expected', 'amount',
        'note', 'occurred_at',
    ];

    protected $casts = [
        'occurred_at'     => 'date',
        'return_expected' => 'date',
        'amount'          => 'integer',
    ];

    public function thing()
    {
        return $this->belongsTo(StfThing::class, 'thing_id');
    }

    public function fromLocation()
    {
        return $this->belongsTo(StfLocation::class, 'from_location_id')
                    ->withTrashed();
    }

    public function toLocation()
    {
        return $this->belongsTo(StfLocation::class, 'to_location_id')
                    ->withTrashed();
    }

    public function expense()
    {
        return $this->hasOne(StfExpense::class, 'register_id');
    }

    // Маппинг event_type → current_status для вещи
    public static function statusFromEvent(string $eventType): string
    {
        return match($eventType) {
            'bought', 'received', 'returned', 'noted' => 'active',
            'ordered'   => 'ordered',
            'moved'     => 'active',
            'installed' => 'installed',
            'lent'      => 'lent',
            'sold'      => 'sold',
            'lost', 'stolen' => 'lost',
            'disposed'  => 'disposed',
            'repaired'  => 'stored',
            default     => 'active',
        };
    }
}


// ══════════════════════════════════════════════════════════════════
// StfExpense.php
// ══════════════════════════════════════════════════════════════════
class StfExpense extends Model
{
    use HasUlids;

    protected $table = 'stf_expenses';

    protected $fillable = [
        'user_id', 'thing_id', 'register_id',
        'transaction_id', 'amount', 'note', 'occurred_at',
    ];

    protected $casts = [
        'occurred_at' => 'date',
        'amount'      => 'integer',
    ];

    public function thing()
    {
        return $this->belongsTo(StfThing::class);
    }

    public function register()
    {
        return $this->belongsTo(StfRegister::class);
    }

    // Транзакция Badger (если привязана)
    public function transaction()
    {
        return $this->belongsTo(BudTransaction::class, 'transaction_id');
    }
}
