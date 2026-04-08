# Sections API — шпаргалка

Все запросы уходят через axios с `withCredentials: true` (JWT в httpOnly cookie).  
Базовый URL: `VITE_API_URL` из `.env`, дефолт `https://api.teftele.com`.

---

## Структура объекта секции

Это то, что фронт ждёт в ответах. Поля берутся из `evt_sections`.  
Необязательные поля можно не слать — фронт проверяет через `?.` и `||`.

```json
{
  "id":          "01HXYZ...",   // ULID, string
  "name":        "Work",        // string, max 32
  "literals":    "WRK",         // string, max 3, nullable — аббревиатура для свёрнутого сайдбара
  "bgcolor":     "#3b5bdb",     // string HEX, nullable — цвет ярлычка и полоски в сайдбаре
  "sort_order":  0,             // integer — порядок в списке
  "is_archived": false,         // boolean
  "is_default":  false          // boolean — дефолтную нельзя удалить (кнопка задизейблена)
}
```

> Поля `color`, `icon`, `description`, `access`, `decor`, `seo` фронт пока не использует —
> можешь слать, не слать, не важно.

---

## Эндпоинты

### GET (POST) — получить все секции юзера

```
POST /eventor/getmysections
Body: {}
```

**Ответ:**
```json
{
  "content": [
    { "id": "01HX...", "name": "Work", "literals": "WRK", "bgcolor": "#3b5bdb", "sort_order": 0, "is_archived": false, "is_default": false },
    { "id": "01HY...", "name": "Personal", "literals": "PER", "bgcolor": "#e03131", "sort_order": 1, "is_archived": false, "is_default": true }
  ]
}
```

Фронт показывает все секции где `is_archived === false`.  
Архивные в сайдбаре и выпадашках **не показываются**, только в менеджере секций (тусклыми).

---

### Создать секцию

```
POST /eventor/savesection
Body:
{
  "name":     "Work",
  "literals": "WRK",
  "bgcolor":  "#3b5bdb"
}
```

**Ответ:**
```json
{
  "content": { /* объект созданной секции целиком */ }
}
```

---

### Обновить секцию

```
POST /eventor/updatesection/{id}
Body: { любые поля которые меняем }
```

Используется в двух сценариях:

**Редактирование** (из формы менеджера):
```json
{ "name": "Work v2", "literals": "WRK", "bgcolor": "#1971c2" }
```

**Архивация / разархивация** (кнопка Archive/Unarchive):
```json
{ "is_archived": true }
// или
{ "is_archived": false }
```

**Ответ:**
```json
{
  "content": { /* объект обновлённой секции целиком */ }
}
```

---

### Удалить секцию

```
DELETE /eventor/deletesection/{id}
```

**Успех:** любой 2xx, тело не важно.

**Если есть связанные события — верни 422:**
```json
{
  "message": "Cannot delete: section has linked events"
}
```

Фронт поймает эту ошибку и покажет нотификацию с кнопкой «Archive instead».  
Текст из `message` покажется пользователю — пиши по-человечески.

---

### Сохранить порядок секций

```
POST /eventor/reordersections
Body:
{
  "sections": [
    { "id": "01HX...", "sort_order": 0 },
    { "id": "01HY...", "sort_order": 1 },
    { "id": "01HZ...", "sort_order": 2 }
  ]
}
```

Фронт шлёт **все активные секции** с новыми `sort_order` после drag-and-drop.  
Архивные в массив не попадают — их порядок не трогай.

**Ответ:** любой 2xx, тело не важно — фронт сам инвалидирует кэш и перезапросит `getmysections`.

---

## Как фронт использует секции в других запросах

При загрузке событий секция летит так:

```
POST /eventor/getmyevents
Body:
{
  "start":    "2026-04-01",
  "end":      "2026-04-30",
  "sections": ["01HX..."]   // массив из одного id
                             // или ["ALL"]  — все секции
                             // или ["NULL"] — события без секции
}
```

---

## Быстрая сводка эндпоинтов

| Метод  | URL                              | Что делает              |
|--------|----------------------------------|-------------------------|
| POST   | `/eventor/getmysections`         | Получить все секции     |
| POST   | `/eventor/savesection`           | Создать секцию          |
| POST   | `/eventor/updatesection/{id}`    | Обновить / архивировать |
| DELETE | `/eventor/deletesection/{id}`    | Удалить (422 если нельзя) |
| POST   | `/eventor/reordersections`       | Сохранить порядок       |