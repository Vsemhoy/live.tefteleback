# Ledger — Codebase Map

> Модуль бюджета и личных финансов.
> Идеология: не бухгалтерия, а **симулятор финансовой реальности** для обычного человека.
> Роут: `/Ledger/*`

---

## Идеология (читать обязательно)

**Ledger — не учёт расходов.** Это инструмент ответа на вопрос:
> "Сколько у меня денег в конкретный день — сегодня, вчера, через месяц?"

Ключевые принципы:

- **Таймлайн-баланс** — баланс виден на каждый день, включая будущее с запланированными транзакциями
- **DnD-первый** — транзакции перетаскиваются между днями и счетами. Shift+drop = копия
- **Группы с toggle** — транзакции объединяются в группы (напр. "Кредит"), группу можно выключить целиком → баланс пересчитывается → сценарное планирование
- **Pending-транзакции** — запланированные платежи участвуют в расчёте баланса
- **Юзер в центре** — никакого двойного учёта, дебета/кредита в UI

**Не ERP, не Битрикс.** Фильтр при разработке: *"Стал бы обычный человек это заполнять?"* — если нет, вырезаем.

---

## Закон проекта: только минорные единицы

❗ **Все суммы и ставки хранятся как целые числа.** Нигде никаких `float`, `decimal`, строк.

```
1 500 ₽  →  150 000   (копейки)
$29.99   →  2 999
23.5%    →  2 350     (ставка * 100)
```

Конвертация — **только в UI**, через `LedgerUtils.js`:

```js
export const toMinor      = (amount)   => Math.round(amount * 100)
export const toMajor      = (minor)    => minor / 100
export const formatMoney  = (minor, currency = 'RUB') =>
  new Intl.NumberFormat('ru-RU', { style: 'currency', currency }).format(minor / 100)

// Процентная ставка
export const rateToInt    = (rate)     => Math.round(parseFloat(rate) * 100) // 23.5 → 2350
export const rateToFloat  = (rateInt)  => rateInt / 100                      // 2350 → 23.5
export const rateToStr    = (rateInt)  => (rateInt / 100).toFixed(2)         // 2350 → "23.50"

// Ежедневное начисление процентов (кредитный счёт)
export const calcDailyInterest = (balance, rateInt, date) => {
  if (!rateInt || balance >= 0) return 0;
  const daysInYear = date.isLeapYear() ? 366 : 365;
  return Math.round(balance * rateInt / 10000 / daysInYear); // отрицательное
};
```

**Почему не decimal:** SQLite его нет нормально, JS нет нативно, API — риск ошибок при сериализации. INT — быстрее, проще, надёжнее.

---

## Архитектура слоёв (заложена, не реализована)

Фундамент под будущее — **git для финансов**.

```
base    → реальные транзакции (один на юзера, создаётся при регистрации)
overlay → прогноз / план
fork    → ветка "что если" (snapshot + свои изменения)
```

`layer_id` присутствует везде в схеме. Весь UI сейчас работает только с `base`. Не реализовывать: fork/merge, override-механику, прогностические счета.

---

## Оптимизация расчёта баланса

```
balance(2026-03-15) =
  closing_balance(2026-02)        ← из Led_month_totals (кэш)
  + delta(2026-03-01..2026-03-15) ← реальные транзакции
  + Σ interest(каждый день)       ← виртуальное, только на фронте
```

**Три режима расчёта:** операционный (таблица дня), агрегатный (графики по month_totals), аудитный (полный пересчёт по кнопке).

**Пересчёт month_totals** — через `LedgerClosingController::recalcFromMonth()`. Вызывается после каждой мутации транзакции. Считает от затронутого месяца вперёд до текущего.

---

## Схема БД (MySQL, Laravel migrations)

### Led_layers
```sql
id           CHAR(26) PK  -- ULID
user_id      CHAR(26)
name         VARCHAR(100)
type         ENUM('base','overlay','fork') DEFAULT 'base'
parent_id    CHAR(26) NULL
is_active    TINYINT(1)   DEFAULT 1
created_at   TIMESTAMP
updated_at   TIMESTAMP
```

### Led_accounts
```sql
id               CHAR(26) PK
user_id          CHAR(26)
layer_id         CHAR(26)
name             VARCHAR(100)
literals         CHAR(3) NULL          -- SBR, CSH, OZN — для collapsed сайдбара
type             ENUM('cash','card','credit','deposit','phantom')
currency         CHAR(3)    DEFAULT 'RUB'
opening_balance  INT        DEFAULT 0  -- в копейках
color            VARCHAR(20) NULL
sort_order       INT        DEFAULT 0
is_archived      TINYINT(1) DEFAULT 0
opened_at        DATE NULL              -- дата открытия счёта
closed_at        DATE NULL              -- дата закрытия (NULL = активен)
interest_rate    INT NULL               -- годовая ставка * 100 (2350 = 23.5%)
interest_start   DATE NULL             -- с какого дня начислять проценты
created_at       TIMESTAMP
updated_at       TIMESTAMP
```

### Led_transaction_groups
```sql
id           CHAR(26) PK
user_id      CHAR(26)
name         VARCHAR(100)
is_disabled  TINYINT(1)  DEFAULT 0
color        VARCHAR(20) NULL
created_at   TIMESTAMP
updated_at   TIMESTAMP
```

### Led_transactions
```sql
id                       CHAR(26) PK
user_id                  CHAR(26)
layer_id                 CHAR(26)
account_id               CHAR(26)
target_account_id        CHAR(26) NULL
group_id                 CHAR(26) NULL
original_transaction_id  CHAR(26) NULL  -- для override в слоях (будущее)

flow_kind    ENUM('expense','income','transfer_out','transfer_in','adjustment','reconciliation')
amount       INT NOT NULL               -- в копейках, всегда > 0
is_negative  TINYINT(1) DEFAULT 0      -- для reconciliation со знаком −
occurred_at  DATE NOT NULL
month_key    CHAR(7) NOT NULL           -- '2026-04'

title        VARCHAR(255) NULL
note         TEXT NULL

status       ENUM('cleared','pending') DEFAULT 'cleared'
is_disabled  TINYINT(1) DEFAULT 0
is_pinned    TINYINT(1) DEFAULT 0
sort_order   INT        DEFAULT 0

linked_entity_type  VARCHAR(50) NULL   -- 'event' | 'stuff_item'
linked_entity_id    CHAR(26)   NULL

created_at   TIMESTAMP
updated_at   TIMESTAMP
deleted_at   TIMESTAMP NULL            -- soft delete
```

### Led_month_totals
```sql
id                  CHAR(26) PK
user_id             CHAR(26)
layer_id            CHAR(26)
account_id          CHAR(26)
month_key           CHAR(7)            -- '2026-04'

opening_balance     INT DEFAULT 0
closing_balance     INT DEFAULT 0
income_total        INT DEFAULT 0
expense_total       INT DEFAULT 0
transfer_in_total   INT DEFAULT 0
transfer_out_total  INT DEFAULT 0
adjustment_total    INT DEFAULT 0
tx_count            INT DEFAULT 0

is_dirty            TINYINT(1) DEFAULT 0
updated_at          TIMESTAMP

UNIQUE KEY (layer_id, account_id, month_key)
```

**Ключевые индексы:**
```sql
Led_transactions: (account_id, month_key), (occurred_at), (group_id), (layer_id)
```

---

## flow_kind — типы транзакций

| flow_kind | Смысл | Цвет | Знак баланса | В итогах месяца |
|-----------|-------|------|--------------|-----------------|
| `expense` | Расход | 🔴 красный | − | expense_total |
| `income` | Доход | 🟢 зелёный | + | income_total |
| `transfer_out` | Перевод (откуда) | 🔵 синий | − | transfer_out_total |
| `transfer_in` | Перевод (куда) | 🔵 синий | + | transfer_in_total |
| `adjustment` | Коррекция копеечных расхождений с банком (кредит) | серый | +/− | adjustment_total |
| `reconciliation` | Сверка/коррекция | 🟣 фиолетовый | +/− (is_negative) | **не входит** |

`reconciliation` — особый тип: не попадает в income/expense итоги, может быть отрицательным (`is_negative=1`). Используется для открытия счетов и ручных коррекций.

Переводы создают **две транзакции** атомарно. Удалять тоже только вместе.

---

## Объекты данных (API → фронт)

Все ответы бэка оборачиваются в `{ status: 1, content: [...] }`. В `LedgerApi.js` используется хелпер `unwrap` который автоматически извлекает `content`:

```js
const unwrap = (r) => r.data?.content ?? r.data ?? [];
```

### Account
```json
{
  "id": "ULID",
  "name": "Тинькофф",
  "literals": "TNK",
  "type": "card",
  "currency": "RUB",
  "opening_balance": 0,
  "color": "#FFDD00",
  "sort_order": 1,
  "is_archived": false,
  "opened_at": "2024-01-01",
  "closed_at": null,
  "interest_rate": null,
  "interest_start": null,
  "balance_today": 150000
}
```

### Transaction
```json
{
  "id": "ULID",
  "account_id": "ULID",
  "flow_kind": "expense",
  "amount": 150000,
  "is_negative": false,
  "occurred_at": "2026-04-10",
  "month_key": "2026-04",
  "title": "Продукты",
  "note": "Пятёрочка\n1000+200=1200",
  "status": "cleared",
  "is_disabled": false,
  "tags": [{ "id", "name", "slug", "color", "bgcolor" }]
}
```

Флаги приходят как `0/1` — всегда приводить через `Boolean()`.

---

## Структура модуля (фронт)

```
modules/Ledger/
├── api/LedgerApi.js
│     unwrap хелпер — все ответы { status, content } → массив
├── store/LedgerStore.js
│     editorOpen/editorParams, readerOpen/readerParams,
│     managerOpen, activeAccounts[], activeCurrency, balanceMode
├── utils/LedgerUtils.js
│     toMinor, toMajor, formatMoney,
│     rateToInt, rateToFloat, rateToStr, calcDailyInterest,
│     flowKindColor, flowKindSign
├── components/
│   ├── TransactionCard/        # draggable, превью note, цвета по flow_kind
│   ├── TransactionEditor/      # модалка, eval в Note (1000+200=?), калькулятор в Amount
│   ├── TransactionReadModal/   # просмотр по двойному клику
│   ├── AccountsSidenav/        # toggle колонок, украден у SectionsSidenav
│   ├── AccountsManager/        # drawer: список DnD + форма с датами/ставкой
│   └── Toolbar/LedgerToolbar.jsx
└── views/
    └── TimelineView/           # ← ГЛАВНЫЙ ВИД (реализован)
```

---

## AccountsSidenav

**Концепция украдена у Eventor/SectionsSidenav** — те же CSS-классы, но клик = toggle колонки.

- Клик на счёт → добавить в `activeAccounts[]`
- Клик другой валюты → сбросить `activeAccounts`, сменить `activeCurrency`
- Активный счёт — зелёный акцент + `balance_today`

---

## TimelineView — главный вид

### Структура (DESC порядок)

```
[Sticky шапка: названия счетов + balance_today]
════════════════════════════════════════════════
[Итоги МАЯ / now]    closing + движения за май
МMMM YYYY ← current
май 31
...
май 1
════════════════════════════════════════════════
[Итоги АПР / end]    closing + движения за апрель
АПРЕЛЬ YYYY
апр 30...апр 1
════════════════════════════════════════════════
[start]              opening — только баланс, без движений
```

### Layout строки дня

```
[52px date] │ [flex:1 AccountSlot] │ [flex:1 AccountSlot] │ [120px TOTAL]
            │  DraggableCard[]     │  DraggableCard[]     │
            │  SlotBalance         │  SlotBalance          │
```

Слоты растягиваются равномерно `flex: 1 1 0`. Карточки внутри ограничены `max-width: 650px`. Горизонтальный скролл если не влезает.

### DnD

- `useDraggable` на карточках, `useDroppable` на слотах (id = `DATE__ACCOUNT_ID`)
- Drop без Shift → `useMoveTransaction` (переместить)
- Drop с Shift → `useSaveTransaction` (скопировать)
- Shift отслеживается через глобальный `keydown/keyup` listener — важно именно при drop
- Между счетами разных валют → тост + отмена
- `DragOverlay` — призрак при перетаскивании

### Баланс по дням

```js
// Для каждого счёта:
opening = prevMonthTotals.closing_balance  // из GET /Ledger/month-totals?month_key=prev
running = opening + Σ реальных транзакций(ASC)

// Кредитный счёт: каждый день после interest_start:
running += calcDailyInterest(running, account.interest_rate, date)
// Начисление прекращается когда running >= 0 (долг погашен)

// Счёт не активен:
if (date < opened_at || date > closed_at) → null (показывается "—")
```

### MonthTotalsRow

Два варианта:
- `variant="closing"` — зелёная строка сверху каждого месяца, показывает движения + баланс на конец
- `variant="opening"` — серая строка снизу последнего месяца, только баланс без движений

---

## Стор (LedgerStore.js)

```js
{
  editorOpen: false,
  editorParams: null,    // { date, account_id } | { id }
  openEditor(params), closeEditor(),

  readerOpen: false,
  readerParams: null,    // { id }
  openReader(params), closeReader(),

  managerOpen: false,
  openManager(), closeManager(),

  activeAccounts: [],    // id счетов — колонки таблицы
  activeCurrency: 'RUB',
  toggleAccount(accountId, currency),

  activeLayerId: null,
  setActiveLayer(layerId),

  balanceMode: 'basic',  // 'basic' | 'extended'
  toggleBalanceMode(),
}
```

---

## API хуки (LedgerApi.js)

| Хук | Описание |
|-----|---------|
| `useAccounts()` | Счета юзера |
| `useTransactions({ start, end, account_id })` | Транзакции за диапазон (account_id — comma-separated) |
| `useTransaction(id)` | Одна транзакция |
| `useMonthTotals({ month_key, account_id })` | Месячные итоги (для opening balance) |
| `useTransactionGroups()` | Группы транзакций |
| `useSaveTransaction()` | Create/update |
| `useDeleteTransaction()` | Soft delete |
| `useMoveTransaction()` | DnD: изменить дату/счёт |
| `useSaveAccount()` | Create/update счёта |
| `useDeleteAccount()` | Удаление |

---

## TransactionEditor — фичи

**Eval в Note** — пишешь `аренда 15000+коммуналка 3500=?` → при вводе `?` после числового выражения мгновенно заменяется на `=18500`. Note рендерится в monospace.

**Калькулятор в Amount** — вводишь `5000+300-500`, поле становится оранжевым, появляется кнопка `=`. Нажимаешь или `Enter` / blur → вычисляется. `safeEval` — только цифры и `+ - * / ( )`.

---

## AccountsManager — поля счёта

| Поле | Тип | Описание |
|------|-----|---------|
| name | string | Название |
| literals | CHAR(3) | SBR, CSH — для collapsed сайдбара |
| type | enum | cash/card/credit/deposit |
| currency | CHAR(3) | RUB/USD/EUR |
| opening_balance | INT | Стартовый баланс в копейках |
| color | hex | Цвет акцента |
| opened_at | DATE | Дата открытия |
| closed_at | DATE | Дата закрытия (NULL = активен) |
| interest_rate | INT | Ставка * 100 (только для credit) |
| interest_start | DATE | С какого дня начислять |

При type=`credit` автоматически раскрывается секция "Credit settings". В форме показывается превью ежедневного начисления.

---

## Бэковые контроллеры

```
app/Http/Controllers/Ledger/
├── LedgerAccountController.php      # CRUD счетов
├── LedgerTransactionController.php  # CRUD + move, инжектит LedgerClosingController
├── LedgerGroupController.php        # CRUD + toggle
├── LedgerMonthTotalsController.php  # GET month-totals
└── LedgerClosingController.php      # recalcFromMonth + getMonthsFrom (не роут)
```

**Важно:** `recalcFromMonth` вызывается после каждой мутации транзакции — store/update/destroy/move. Вызов **за пределами** `DB::transaction` чтобы не откатил транзакцию при ошибке пересчёта.

---

## Известные грабли

| Грабля | Решение |
|--------|---------|
| Бэк отдаёт `{ status, content }` | `unwrap` хелпер в LedgerApi.js — `r.data?.content ?? r.data ?? []` |
| `Boolean(0)` | Бэк отдаёт флаги как `0/1` — всегда `Boolean()` перед использованием |
| Перевод = 2 транзакции | Создаются атомарно в `DB::transaction`. Удалять только вместе |
| DnD между месяцами | При move инвалидируются `is_dirty` на обоих месяцах |
| Shift при drop | Отслеживаем через глобальный `keydown/keyup` в `useEffect` — читаем при drop |
| Баланс без opening | `useMonthTotals` запрашивает prev month, `closing_balance` = opening следующего |
| interest_rate как DECIMAL | Хранить как INT (2350 = 23.5%) — `rateToInt/rateToFloat` для конвертации |
| Колонки не сжимаются | `flex: 0 0 250px` на мобилке, `min-width: max-content` на строках |

---

## Очередь фич

- [x] TimelineView — DnD таймлайн
- [x] AccountsSidenav — toggle колонок
- [x] TransactionCard — цвета, превью note, draggable
- [x] TransactionEditor — eval в note, калькулятор в amount
- [x] TransactionReadModal — просмотр по двойному клику
- [x] AccountsManager — DnD, даты, кредитные настройки
- [x] MonthTotalsRow — closing/opening строки
- [x] Кредитный счёт — ежедневное начисление процентов на фронте
- [x] Reconciliation — сверка/коррекция со знаком
- [ ] Подписки (Led_subscriptions) — хостинги, домены, телефон
- [ ] Поиск по транзакциям (SearchPanel)
- [ ] MonthView — месячная таблица
- [ ] StatsView — графики по month_totals
- [ ] Связь с Eventor (linked_entity)
- [ ] Слои / прогнозные сценарии (Этап 2+)
- [ ] Миграция данных из старого Ledger (okker_local)
