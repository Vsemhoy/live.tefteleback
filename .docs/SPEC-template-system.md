# Teftele — Система шаблонов (sys_templates)

> Спецификация. Один движок, все модули, живые переменные.

---

## Идея в одном абзаце

Шаблон — это сохранённый набор полей события/транзакции с переменными внутри значений. При создании переменные резолвятся: авто-значения подставляются молча, интерактивные — показываются как поля ввода. Один тап — событие создано с предзаполнением. Как сниппеты VS Code, только для жизни.

---

## БД

```sql
CREATE TABLE sys_templates (
  id          CHAR(26) PRIMARY KEY,   -- ULID
  module      VARCHAR(16) NOT NULL,   -- 'eventor' | 'ledger' | 'exploiter'
  name        VARCHAR(128) NOT NULL,  -- 'Рабочий день' | 'Столовая'
  icon        VARCHAR(32) NULL,       -- tabler icon slug: 'briefcase', 'soup'
  payload     JSON NOT NULL,          -- поля целевой сущности с {{переменными}}
  sort_order  INT DEFAULT 0,
  user_id     CHAR(26) NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_user_module (user_id, module)
);
```

### Contentor

Поле `content` в `payload` считается виртуальным markdown-полем. Шаблон может
держать его рядом с обычными полями формы, но при публикации модуль не обязан
иметь колонку `content`: markdown уходит в `cnt_contents`.

```sql
CREATE TABLE cnt_contents (
  id            CHAR(26) PRIMARY KEY,
  user_id       CHAR(26) NOT NULL,
  source_module VARCHAR(32) NOT NULL,   -- 'eventor' | 'exploiter' | 'ledger.transaction'
  source_id     CHAR(26) NOT NULL,
  field         VARCHAR(64) DEFAULT 'content',
  kind          VARCHAR(32) DEFAULT 'markdown',
  title         VARCHAR(255) NULL,
  body_md       LONGTEXT NOT NULL,
  body_hash     CHAR(64) NULL,
  locale        VARCHAR(10) NULL,
  status        TINYINT DEFAULT 1,
  is_primary    BOOLEAN DEFAULT FALSE,
  sort_order    INT DEFAULT 0,
  meta          JSON NULL
);
```

На `source_module/source_id/field/kind` нужен индекс, но не `UNIQUE`: у одного
родителя могут появиться несколько markdown-блоков, версии, Booker-блоки или
другие текстовые секции.

Позже (псевдо-автоматизация):
```sql
ALTER TABLE sys_templates ADD COLUMN schedule JSON NULL;
-- { "type": "weekdays" }
-- { "type": "monthly", "day": 1 }
-- { "type": "weekly", "days": [1, 3, 5] }
-- null = ручной запуск
```

---

## Синтаксис переменных

Всё внутри двойных фигурных: `{{...}}`. Три категории:

### 1. Авто-резолв

Подставляются молча, без участия юзера.

| Переменная | Пример результата | Описание |
|---|---|---|
| `{{today}}` | `2026-07-08` | ISO-дата сегодня |
| `{{now}}` | `14:35` | Текущее время HH:mm |
| `{{weekday}}` | `Вторник` | День недели на русском |
| `{{weekday_short}}` | `Вт` | Короткий день недели |
| `{{month}}` | `Июль` | Название месяца |
| `{{year}}` | `2026` | Год |
| `{{user.name}}` | `Адам` | Имя текущего юзера |
| `{{start_of_month}}` | `2026-07-01` | Первый день месяца |
| `{{end_of_month}}` | `2026-07-31` | Последний день месяца |

### 2. Функции-модификаторы

Вычисляемые значения на базе авто-переменных.

| Выражение | Результат | Описание |
|---|---|---|
| `{{now + hours(9)}}` | `23:35` | Текущее время + 9 часов |
| `{{now - minutes(30)}}` | `14:05` | Минус 30 минут |
| `{{today + days(7)}}` | `2026-07-15` | Через неделю |
| `{{today - days(1)}}` | `2026-07-07` | Вчера |
| `{{today + months(1)}}` | `2026-08-08` | Через месяц |
| `{{end_of_month + days(1)}}` | `2026-08-01` | Первый день след. месяца |

Модификаторы: `hours(n)`, `minutes(n)`, `days(n)`, `months(n)`.
Операторы: `+`, `-`. Цепочки не поддерживаются (одна операция).

### 3. Интерактивные (tab-stops)

Поля ввода, которые юзер заполняет при создании. Курсор прыгает по ним.

| Синтаксис | Тип поля | Описание |
|---|---|---|
| `{{input:Название}}` | текст | Пустое текстовое поле |
| `{{input:Начало\|now}}` | текст | С дефолтом = текущее время |
| `{{input:Конец\|now + hours(9)}}` | текст | С вычисляемым дефолтом |
| `{{number:Сумма\|250}}` | число | Числовое, дефолт 250 |
| `{{number:Литры}}` | число | Числовое без дефолта |
| `{{select:Настроение\|Отлично,Норм,Устал}}` | дропдаун | Варианты через запятую |
| `{{select:Приоритет\|13:Обычный,14:Высокий,15:Критично}}` | дропдаун | С числовыми значениями |

Формат: `{{тип:Лейбл|дефолт_или_варианты}}`.
Лейбл — то, что видит юзер. Дефолт может быть значением или выражением.

---

## Парсер — `shared/utils/templateEngine.js`

### API

```js
resolveTemplate(payload, context) → {
  resolved: { ...payload с подставленными авто-значениями },
  fields: [
    { key: 'Начало', type: 'input',  default: '09:00',  path: 'content' },
    { key: 'Конец',  type: 'input',  default: '18:00',  path: 'content' },
    { key: 'Сумма',  type: 'number', default: 250,       path: 'amount' },
    ...
  ]
}
```

- `resolved` — payload, где авто-переменные уже подставлены, а tab-stops заменены на дефолты (для превью)
- `fields` — массив интерактивных полей для рендера формы
- `path` — в каком поле payload живёт эта переменная (для обратной записи)
- `context` — `{ user, now, today }` — передаётся снаружи

### Внутренняя механика

```js
// 1. Пройти по всем строковым значениям payload рекурсивно (включая вложенный JSON)
// 2. Для каждого {{...}}:
//    a. Авто → подставить значение из VARS
//    b. Функция → вычислить через MODS
//    c. input/number/select → добавить в fields[], подставить дефолт в resolved
// 3. Вернуть { resolved, fields }
```

### Регистр переменных

```js
const VARS = {
  today:          () => new Date().toISOString().slice(0, 10),
  now:            () => new Date().toTimeString().slice(0, 5),
  weekday:        () => ['Воскресенье','Понедельник','Вторник','Среда',
                         'Четверг','Пятница','Суббота'][new Date().getDay()],
  weekday_short:  () => ['Вс','Пн','Вт','Ср','Чт','Пт','Сб'][new Date().getDay()],
  month:          () => ['Январь','Февраль','Март','Апрель','Май','Июнь',
                         'Июль','Август','Сентябрь','Октябрь','Ноябрь',
                         'Декабрь'][new Date().getMonth()],
  year:           () => String(new Date().getFullYear()),
  'user.name':    (ctx) => ctx.user?.name || '',
  start_of_month: () => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-01`; },
  end_of_month:   () => { const d = new Date(); return new Date(d.getFullYear(), d.getMonth()+1, 0).toISOString().slice(0,10); },
};

const MODS = {
  hours:   (base, n) => /* добавить n часов к time-строке */,
  minutes: (base, n) => /* добавить n минут к time-строке */,
  days:    (base, n) => /* добавить n дней к date-строке */,
  months:  (base, n) => /* добавить n месяцев к date-строке */,
};
```

Парсер **модуле-агностичен**: не знает про Eventor/Ledger/Exploiter.
Модуль решает, какие поля payload показать и куда записать результат. После
резолва `content` передается publish-adapter'у как markdown для Contentor.

---

## Примеры шаблонов по модулям

### Eventor

**«Рабочий день»:**
```json
{
  "module": "eventor",
  "name": "Рабочий день",
  "icon": "briefcase",
  "payload": {
    "section_id": "sec-work",
    "type_id": "type-state",
    "access": 1,
    "name": "Рабочий день — {{weekday}}, {{today}}",
    "content": "Начало: {{input:Начало|now}}\nКонец: {{input:Конец|now + hours(9)}}\n\n## Задачи\n- {{input:Главная задача}}\n\n## Итоги\n{{input:Что сделал}}"
  }
}
```

**«Созвон с командой»:**
```json
{
  "module": "eventor",
  "name": "Созвон",
  "icon": "phone",
  "payload": {
    "section_id": "sec-work",
    "type_id": "type-meeting",
    "access": 1,
    "name": "Созвон — {{input:Тема|Статус}}",
    "content": "Время: {{input:Время|now}}\nУчастники: {{input:Кто}}\n\n## Обсудили\n{{input:Заметки}}"
  }
}
```

### Ledger

**«Столовая»:**
```json
{
  "module": "ledger",
  "name": "Столовая",
  "icon": "soup",
  "payload": {
    "account_id": "acc-card",
    "category_id": "cat-food",
    "name": "Столовая",
    "amount": "{{number:Сумма|-250}}",
    "occurred_at": "{{today}}"
  }
}
```

**«Зарплата»:**
```json
{
  "module": "ledger",
  "name": "Зарплата",
  "icon": "cash",
  "payload": {
    "account_id": "acc-card",
    "category_id": "cat-salary",
    "name": "Зарплата {{month}}",
    "amount": "{{number:Сумма|150000}}",
    "occurred_at": "{{input:Дата|end_of_month}}"
  }
}
```

**«Аренда»:**
```json
{
  "module": "ledger",
  "name": "Аренда",
  "icon": "home",
  "payload": {
    "account_id": "acc-card",
    "category_id": "cat-rent",
    "name": "Аренда {{month}}",
    "amount": -3500000,
    "occurred_at": "{{input:Дата|start_of_month}}"
  }
}
```

### Exploiter

**«Заправка»:**
```json
{
  "module": "exploiter",
  "name": "Заправка",
  "icon": "gas-station",
  "payload": {
    "thing_id": "thing-bmw",
    "name": "Заправка {{today}}",
    "status": 22,
    "priority": 13,
    "part_cost": "{{number:Сумма|2500}}",
    "note": "{{input:АЗС|Лукойл}}, пробег {{input:Пробег}} км"
  }
}
```

**«Ремонт авто»:**
```json
{
  "module": "exploiter",
  "name": "Ремонт авто",
  "icon": "car",
  "payload": {
    "thing_id": "thing-bmw",
    "name": "{{input:Что делали}}",
    "status": 20,
    "priority": "{{select:Приоритет|13:Обычный,14:Высокий,15:Критично}}",
    "part_cost": "{{number:Деталь|0}}",
    "labor_cost": "{{number:Работа|0}}",
    "details": {
      "performer": "{{input:Сервис}}",
      "phone": "{{input:Телефон}}",
      "address": "{{input:Адрес}}"
    }
  }
}
```

**«ТО»:**
```json
{
  "module": "exploiter",
  "name": "ТО",
  "icon": "settings",
  "payload": {
    "thing_id": "thing-bmw",
    "name": "ТО — {{input:Что меняли|Масло + фильтры}}",
    "status": 22,
    "priority": 13,
    "part_cost": "{{number:Материалы|3500}}",
    "labor_cost": "{{number:Работа|0}}",
    "note": "Пробег: {{input:Пробег}} км"
  }
}
```

---

## UI-флоу

### Создание из шаблона

```
1. Юзер нажимает «+» или кнопку «Шаблоны» в тулбаре
2. Выпадает список шаблонов модуля (иконка + название)
3. Тап по шаблону →
4. Модалка:
   ┌─────────────────────────────────────────┐
   │  🔧 Ремонт авто                        │
   │                                         │
   │  Авто-резолв:                           │
   │  today → 2026-07-08  weekday → Вторник  │
   │                                         │
   │  Заполни:                               │
   │  Что делали  [___________________]      │
   │  Приоритет   [Обычный       ▾]          │
   │  Деталь      [          0    ₽]         │
   │  Работа      [          0    ₽]         │
   │  Сервис      [___________________]      │
   │  Телефон     [___________________]      │
   │                                         │
   │  Превью:                                │
   │  ┌─ Ремонт — Замена колодок ──────────┐ │
   │  │  08.07.2026 · Запланировано        │ │
   │  │  Деталь: 4500 ₽ · Работа: 1500 ₽  │ │
   │  └────────────────────────────────────┘ │
   │                                         │
   │                  [Отмена]  [Создать]     │
   └─────────────────────────────────────────┘
5. Tab прыгает между полями (tab-stops)
6. Превью обновляется на лету
7. «Создать» → сущность создана → модалка закрылась
```

### Создание / редактирование шаблона

```
1. Кнопка «Новый шаблон» / «Редактировать» в списке
2. Форма:
   - Название шаблона
   - Иконка (select из Tabler icons)
   - Модуль (eventor / ledger / exploiter)
   - Поля payload:
     Вариант А: JSON-редактор (для продвинутых)
     Вариант Б: визуальный билдер — список полей модуля,
                для каждого: значение или {{переменная}}
3. Внизу — справочник переменных (шпаргалка)
4. Сохранить → sys_templates
```

### Расположение в UI

**Вариант 1 — кнопка в тулбаре:**
Рядом с «+» → дропдаун с шаблонами модуля.
Плюс: не занимает место, работает в любом вью.

**Вариант 2 — секция в сайдбаре:**
Под навигацией, сворачиваемый блок «Шаблоны».
Плюс: всегда видны, быстрый доступ.

Рекомендация: **оба** — в тулбаре дропдаун для быстрого доступа,
в настройках модуля — управление шаблонами.

---

## Псевдо-автоматизация (этап 2)

Шаблон + расписание. Не крон, не автосоздание — **напоминание**.

### Механика

- `schedule` поле в `sys_templates` (JSON, nullable)
- Фронт при загрузке проверяет: есть ли шаблоны с schedule,
  у которых сегодня день срабатывания?
- Если да → показывает призрак-карточку вверху ленты/таймлайна:
  «Создать "Рабочий день"?» → [Создать] [Пропустить]
- Если юзер не тапнул — пропало, не засоряет
- Не нужен бэк/крон, вся логика на фронте

### Типы расписаний

```json
{ "type": "daily" }
{ "type": "weekdays" }
{ "type": "weekly", "days": [1, 3, 5] }
{ "type": "monthly", "day": 1 }
{ "type": "monthly", "day": -1 }          // последний день месяца
{ "type": "yearly", "month": 3, "day": 8 }
```

### Проверка на фронте

```js
// shared/utils/scheduleCheck.js
const shouldFire = (schedule, today) => {
  const d = new Date(today);
  switch (schedule.type) {
    case 'daily':    return true;
    case 'weekdays': return d.getDay() >= 1 && d.getDay() <= 5;
    case 'weekly':   return schedule.days.includes(d.getDay());
    case 'monthly':  return schedule.day === -1
                       ? d.getDate() === new Date(d.getFullYear(), d.getMonth()+1, 0).getDate()
                       : d.getDate() === schedule.day;
    case 'yearly':   return d.getMonth()+1 === schedule.month && d.getDate() === schedule.day;
    default: return false;
  }
};
```

---

## Граничные случаи

| Кейс | Поведение |
|---|---|
| Переменная не распознана | Оставить как есть: `{{unknown}}` → `{{unknown}}` |
| Дефолт = выражение, вычисление сломалось | Fallback на пустую строку |
| Payload содержит вложенный JSON (details) | Парсер рекурсивно обходит все строковые значения |
| Числовой дефолт в строковом поле | Приводить к строке |
| Шаблон привязан к несуществующему account_id | Валидация при создании: «Счёт не найден, выбери другой» |
| Два tab-stops с одинаковым лейблом | Ошибка в редакторе шаблона |
