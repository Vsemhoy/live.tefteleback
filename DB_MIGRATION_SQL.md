# SQL Migration для ULID

## Проблема
Таблицы на хостинге созданы через SQL с `CHAR(26)` вместо `ULID()` из Laravel миграций.

## Решение

### 1. Обновить типы колонок везде, где используется ID

```sql
-- stf_locations
ALTER TABLE stf_locations MODIFY id CHAR(26) NOT NULL;
ALTER TABLE stf_locations MODIFY user_id CHAR(26) NOT NULL;
ALTER TABLE stf_locations MODIFY parent_id CHAR(26);

-- stf_things
ALTER TABLE stf_things MODIFY id CHAR(26) NOT NULL;
ALTER TABLE stf_things MODIFY user_id CHAR(26) NOT NULL;
ALTER TABLE stf_things MODIFY parent_id CHAR(26);
ALTER TABLE stf_things MODIFY category_id CHAR(26);
ALTER TABLE stf_things MODIFY current_location_id CHAR(26);

-- stf_register
ALTER TABLE stf_register MODIFY id CHAR(26) NOT NULL;
ALTER TABLE stf_register MODIFY user_id CHAR(26) NOT NULL;
ALTER TABLE stf_register MODIFY thing_id CHAR(26) NOT NULL;
ALTER TABLE stf_register MODIFY from_location_id CHAR(26);
ALTER TABLE stf_register MODIFY to_location_id CHAR(26);

-- stf_expenses
ALTER TABLE stf_expenses MODIFY id CHAR(26) NOT NULL;
ALTER TABLE stf_expenses MODIFY user_id CHAR(26) NOT NULL;
ALTER TABLE stf_expenses MODIFY thing_id CHAR(26) NOT NULL;
ALTER TABLE stf_expenses MODIFY register_id CHAR(26);
ALTER TABLE stf_expenses MODIFY transaction_id CHAR(26);
```

### 2. Убрать trait HasUlids из моделей и добавить явные настройки

В файле `app/Models/StfRegister.php`:
```php
// Убрать: use Illuminate\Database\Eloquent\Concerns\HasUlids;

class StfRegister extends Model
{
    protected $table = 'stf_register';
    
    protected $keyType = 'string';
    public $incrementing = false;
    
    // ... остальное без изменений
}
```

И аналогично для других моделей, если они используют char вместо ulid.

### 3. Или альтернативно — пересоздать таблицы с правильными типами

Если есть возможность — удалите старые таблицы и запустите миграции:

```bash
php artisan migrate:fresh
```

Но это удалит все данные!

## Рекомендация

Используйте первый вариант (ALTER TABLE) чтобы сохранить данные.
