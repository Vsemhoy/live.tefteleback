# Tags API — шпаргалка

---

## Структура объекта тега

```json
{
  "id":        "SYSTAG0000000000000URGENT00",
  "name":      "Urgent",
  "slug":      "urgent",
  "color":     "#e03131",
  "bgcolor":   "#ffe3e3",
  "is_system": true,
  "sort_order": 1000
}
```

---

## Эндпоинты

### Получить теги пользователя

```
POST /eventor/getmytags
Body: {}
```

Возвращает системные теги + личные теги юзера, отсортированные по `sort_order`:

```php
// Логика в контроллере
$tags = EvtTag::where(function ($q) use ($userId) {
    $q->where('user_id', $userId)
      ->orWhere('is_system', true);
})
->where('is_archived', false)
->orderBy('sort_order')
->get(['id', 'name', 'slug', 'color', 'bgcolor', 'is_system', 'sort_order']);
```

**Ответ:**
```json
{
  "content": [ /* массив тегов */ ]
}
```

---

### Создать тег

```
POST /eventor/savetag
Body: { "name": "grocery", "color": "#2f9e44", "bgcolor": "#ebfbee" }
```

- `slug` генерируется на бэке из `name` (Str::slug)
- `user_id` берётся из auth
- `is_system` всегда `false` для юзерских тегов

**Ответ:** `{ "content": { /* созданный тег */ } }`

---

### Обновить тег

```
POST /eventor/updatetag/{id}
Body: { "name": "...", "color": "...", "bgcolor": "..." }
```

Системные теги (`is_system = true`) — защитить от изменения, вернуть 403.

---

### Удалить тег

```
DELETE /eventor/deletetag/{id}
```

- Системные теги удалять нельзя → 403
- При удалении тега `evt_event_tags` чистится каскадом (FK)

---

### Теги в getmyevents

В ответе каждого события добавить связанные теги:

```php
// В EventResource или в запросе
->with('tags:id,name,slug,color,bgcolor')
```

Результат в объекте события:
```json
{
  "id": "01KN...",
  "name": "Купить фильтры",
  "tags": [
    { "id": "SYSTAG00000000000000BUY000", "name": "Buy", "slug": "buy", "color": "#0c8599", "bgcolor": "#e3fafc" },
    { "id": "01KN...userTag", "name": "car", "slug": "car", "color": null, "bgcolor": null }
  ],
  ...
}
```

---

### Сохранить теги ивента

При `saveevent` / `updateevent` принимать массив `tag_ids`:

```
POST /eventor/saveevent
Body: {
  "name": "...",
  "tag_ids": ["SYSTAG00000000000000BUY000", "01KN..."]
}
```

```php
// В контроллере после сохранения ивента
$event->tags()->sync($request->tag_ids ?? []);
```

`sync()` сам добавит новые и удалит убранные теги — идеально для MultiSelect.

---

## Модель EvtEvent — добавить связь

```php
public function tags(): BelongsToMany
{
    return $this->belongsToMany(
        EvtTag::class,
        'evt_event_tags',
        'event_id',
        'tag_id'
    );
}
```

## Модель EvtTag — добавить связь

```php
public function events(): BelongsToMany
{
    return $this->belongsToMany(
        EvtEvent::class,
        'evt_event_tags',
        'tag_id',
        'event_id'
    );
}
```

---

## Быстрая сводка эндпоинтов

| Метод  | URL                        | Что делает                    |
|--------|----------------------------|-------------------------------|
| POST   | `/eventor/getmytags`       | Получить все теги (свои + системные) |
| POST   | `/eventor/savetag`         | Создать тег                   |
| POST   | `/eventor/updatetag/{id}`  | Обновить тег                  |
| DELETE | `/eventor/deletetag/{id}`  | Удалить тег                   |

Теги ивента сохраняются через `tag_ids[]` в `saveevent` / `updateevent` через `sync()`.
