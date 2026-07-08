Отлично! Создам полный API spec для Eventor модуля в формате Markdown таблиц.

## 📋 API Specification — Teftele Eventor

Вот полная спецификация всех API endpoints для Eventor модуля:

### 🔐 Авторизация и управление сессией

| Method | Endpoint | Description | Auth | Request Body | Response |
|--------|----------|-------------|------|--------------|----------|
| POST | `/auth/login` | Логин пользователя | No | `{ email, password }` | `{ user: {id, name, email, avatar}, message }` |
| POST | `/auth/logout` | Выход из системы | Yes | - | `{ message }` |
| POST | `/auth/refresh` | Рефреш JWT токена | Yes (cookies) | - | `{ message }` |
| POST | `/auth/me` | Получение текущего пользователя | Yes | - | `{ user: {id, name, email, avatar} }` |

### 📝 События (Events)

| Method | Endpoint | Description | Auth | Request Body | Response |
|--------|----------|-------------|------|--------------|----------|
| POST | `/eventor/getmyevents` | Получение событий за период | Yes | `{ start, end, sections: [] }` | `{ content: [event] }` |
| GET | `/eventor/getmyevent/:id` | Получение одного события | Yes | - | `{ content: event }` |
| POST | `/eventor/saveevent` | Создание нового события | Yes | `event data` | `{ content: savedEvent }` |
| POST | `/eventor/updateevent/:id` | Обновление события | Yes | `event data` | `{ content: updatedEvent }` |
| DELETE | `/eventor/deleteevent/:id` | Удаление события | Yes | - | `{ message }` |
| POST | `/eventor/search` | Полнотекстовый поиск | Yes | `{ q, sections, types, date_from, date_to, page, per_page }` | `{ content: [events], total, page, pages }` |

### 📂 Секции (Sections)

| Method | Endpoint | Description | Auth | Request Body | Response |
|--------|----------|-------------|------|--------------|----------|
| POST | `/eventor/getmysections` | Получение секций пользователя | Yes | - | `{ content: [section] }` |
| POST | `/eventor/savesection` | Создание новой секции | Yes | `{ name, description? }` | `{ content: savedSection }` |
| POST | `/eventor/updatesection/:id` | Обновление секции | Yes | `{ id, name, description? }` | `{ content: updatedSection }` |

### 🏷️ Типы событий (Event Types)

| Method | Endpoint | Description | Auth | Request Body | Response |
|--------|----------|-------------|------|--------------|----------|
| POST | `/eventor/getmytypes` | Получение типов событий | Yes | - | `{ content: [type] }` |

### 📊 Drafts (Локальные черновики)

Эти endpoints не требуют API вызовов — они работают через **IndexedDB** локально:

| Operation | Method | Description |
|-----------|--------|-------------|
| Создать черновик | `createDraft(data)` | Сохраняет в IndexedDB当 network is offline или user not logged in |
| Обновить черновик | `updateDraft(localId, data)` | Обновляет существующий черновик |
| Удалить черновик | `deleteDraft(localId)` | Удаляет черновик после sync |
| Получить pending drafts | `getPendingDrafts()` | Получает все черновики со статусом `pending` или `error` |

---

### 📊 Структура данных (Data Models)

#### Event
```javascript
{
  id: number,           // ID события (при создании может отсутствовать)
  name: string,         // Название события (опционально)
  content: string,      // Markdown контент события
  setdate: string,      // Дата и время в формате 'YYYY-MM-DD HH:mm:ss'
  section_id: number|null,  // ID секции (nullable)
  type_id: number,      // ID типа события
  user_id: number,      // ID владельца
  created_at: string,   // Время создания
  updated_at: string,   // Время обновления
}
```

#### Section
```javascript
{
  id: number,
  name: string,
  description?: string,
  user_id: number,
  created_at: string,
  updated_at: string,
}
```

#### EventType
```javascript
{
  id: number,
  name: string,
  color?: string,       // Цвет для UI (hex)
  bgcolor?: string,     // Цвет фона для UI (hex)
  user_id: number,
  created_at: string,
  updated_at: string,
}
```

#### Draft
```javascript
{
  localId: string,      // UUID (client-side generated)
  name: string,
  content: string,
  setdate: string,
  section_id: number|null,
  type_id: number,
  created_at: string,
  updated_at: string,
  syncStatus: 'pending' | 'synced' | 'error',
  errorMsg?: string,
}
```

---

### 🔑 Auth Flow

1. **Login**: `POST /auth/login` → сервер устанавливает httpOnly cookies (access + refresh tokens)
2. **API Calls**: axios автоматически отправляет cookies с каждым запросом
3. **401 Error**: interceptor перехватывает, вызывает `/auth/refresh`, ставит запросы в очередь
4. **Refresh Success**: повторяет все запросы из очереди
5. **Refresh Fail**: dispatches `auth:logout` event → clear local state

---

### ⚡ Offline-first Strategy

- ✅ When online + logged in → save to API
- ✅ When offline OR not logged in → save to IndexedDB as draft
- ✅ When back online → sync pending drafts to server

---

Хочешь чтобы я:
1. Создал файл `API.md` в корне проекта с этим spec?
2. Добавил примеры curl запросов для тестирования?
3. Описал будущие endpoints для других модулей (Exploiter, Ledger, Tasker, PM)?