<?php

namespace Database\Seeders;

use App\Models\EvtEvent;
use App\Models\EvtSection;
use App\Models\EvtType;
use App\Models\LedAccount;
use App\Models\LedCategory;
use App\Models\LedTransaction;
use App\Models\StfLocation;
use App\Models\StfRegister;
use App\Models\StfThing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    private string $userId;
    private Carbon $now;

    // ─── Справочники ───────────────────────────────────────────────
    private array $sections  = [];
    private array $evtTypes  = [];
    private array $ledCats   = [];
    private array $ledAccounts = [];
    private array $locations = [];
    private array $things    = [];

    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => env('DEMO_USER_EMAIL', 'demo@teftele.com')],
            [
                'name'     => env('DEMO_USER_NAME', 'Алекс Демо'),
                'password' => Hash::make(env('DEMO_USER_PASSWORD', 'demo1234')),
                'status'   => User::STATUS_ACTIVE,
            ]
        );

        $this->userId = $user->id;
        $this->now    = Carbon::now();

        $this->command->info('Demo user: ' . $user->email);

        $this->seedSections();
        $this->seedEventTypes();
        $this->seedLedgerCategories();
        $this->seedLedgerAccounts();
        $this->seedLocations();
        $this->seedThings();

        // ── Основной контент ──────────────────────────────────────
        $this->seedCarHistory();      // ~220 событий Exploiter за 2 года
        $this->seedWorkEvents();      // ~400 рабочих дней в Eventor
        $this->seedPersonalEvents();  // ~80 личных событий
        $this->seedTransactions();    // ~300 транзакций Ledger

        $this->command->info('Demo seeding done!');
    }

    // ══════════════════════════════════════════════════════════════
    // СПРАВОЧНИКИ
    // ══════════════════════════════════════════════════════════════

    private function seedSections(): void
    {
        $defs = [
            ['name' => 'Работа',   'color' => '#1971c2', 'bgcolor' => '#e7f5ff', 'icon' => 'briefcase'],
            ['name' => 'Личное',   'color' => '#2f9e44', 'bgcolor' => '#ebfbee', 'icon' => 'user'],
            ['name' => 'Авто',     'color' => '#e8590c', 'bgcolor' => '#fff4e6', 'icon' => 'car'],
            ['name' => 'Здоровье', 'color' => '#c92a2a', 'bgcolor' => '#fff5f5', 'icon' => 'heart'],
            ['name' => 'Учёба',    'color' => '#5f3dc4', 'bgcolor' => '#f3f0ff', 'icon' => 'book'],
        ];
        foreach ($defs as $i => $d) {
            $s = EvtSection::updateOrCreate(
                ['user_id' => $this->userId, 'name' => $d['name']],
                array_merge($d, ['user_id' => $this->userId, 'sort_order' => $i + 1, 'access' => 1])
            );
            $this->sections[$d['name']] = $s->id;
        }
    }

    private function seedEventTypes(): void
    {
        $defs = [
            ['title' => 'Запись',     'color' => '#495057', 'bgcolor' => '#f8f9fa', 'icon' => 'pencil'],
            ['title' => 'Встреча',    'color' => '#1971c2', 'bgcolor' => '#e7f5ff', 'icon' => 'users'],
            ['title' => 'Задача',     'color' => '#2f9e44', 'bgcolor' => '#ebfbee', 'icon' => 'check'],
            ['title' => 'Событие',    'color' => '#e8590c', 'bgcolor' => '#fff4e6', 'icon' => 'calendar'],
            ['title' => 'Состояние',  'color' => '#5f3dc4', 'bgcolor' => '#f3f0ff', 'icon' => 'activity'],
        ];
        foreach ($defs as $i => $d) {
            $t = EvtType::updateOrCreate(
                ['user_id' => $this->userId, 'title' => $d['title']],
                array_merge($d, ['user_id' => $this->userId, 'sort_order' => $i + 1])
            );
            $this->evtTypes[$d['title']] = $t->id;
        }
    }

    private function seedLedgerCategories(): void
    {
        // Родительские категории
        $parents = [
            ['name' => 'Доходы',      'sort' => 1],
            ['name' => 'Авто',        'sort' => 2],
            ['name' => 'Жильё',       'sort' => 3],
            ['name' => 'Еда',         'sort' => 4],
            ['name' => 'Здоровье',    'sort' => 5],
            ['name' => 'Транспорт',   'sort' => 6],
            ['name' => 'Подписки',    'sort' => 7],
            ['name' => 'Одежда',      'sort' => 8],
            ['name' => 'Развлечения', 'sort' => 9],
            ['name' => 'Прочее',      'sort' => 10],
        ];

        foreach ($parents as $p) {
            $cat = LedCategory::updateOrCreate(
                ['user_id' => $this->userId, 'name' => $p['name'], 'parent_id' => null],
                ['user_id' => $this->userId, 'name' => $p['name'], 'depth' => 0,
                 'sort_order' => $p['sort'], 'path' => $p['name']]
            );
            $this->ledCats[$p['name']] = $cat->id;
        }

        // Подкатегории Авто
        $autoSubs = ['Топливо', 'Кузов', 'Подвеска', 'Двигатель', 'ТО', 'Шины', 'Страховка', 'Штрафы'];
        foreach ($autoSubs as $i => $n) {
            $cat = LedCategory::updateOrCreate(
                ['user_id' => $this->userId, 'name' => $n, 'parent_id' => $this->ledCats['Авто']],
                ['user_id' => $this->userId, 'name' => $n, 'depth' => 1,
                 'parent_id' => $this->ledCats['Авто'], 'sort_order' => $i + 1,
                 'path' => 'Авто/' . $n]
            );
            $this->ledCats['Авто/' . $n] = $cat->id;
        }

        // Подкатегории Еда
        $foodSubs = ['Столовая', 'Продукты', 'Доставка', 'Кафе'];
        foreach ($foodSubs as $i => $n) {
            $cat = LedCategory::updateOrCreate(
                ['user_id' => $this->userId, 'name' => $n, 'parent_id' => $this->ledCats['Еда']],
                ['user_id' => $this->userId, 'name' => $n, 'depth' => 1,
                 'parent_id' => $this->ledCats['Еда'], 'sort_order' => $i + 1,
                 'path' => 'Еда/' . $n]
            );
            $this->ledCats['Еда/' . $n] = $cat->id;
        }

        // Подкатегории Доходы
        $incomeSubs = ['Зарплата', 'Фриланс', 'Прочее'];
        foreach ($incomeSubs as $i => $n) {
            $cat = LedCategory::updateOrCreate(
                ['user_id' => $this->userId, 'name' => $n, 'parent_id' => $this->ledCats['Доходы']],
                ['user_id' => $this->userId, 'name' => $n, 'depth' => 1,
                 'parent_id' => $this->ledCats['Доходы'], 'sort_order' => $i + 1,
                 'path' => 'Доходы/' . $n]
            );
            $this->ledCats['Доходы/' . $n] = $cat->id;
        }
    }

    private function seedLedgerAccounts(): void
    {
        $accounts = [
            ['name' => 'Карта Т-Банк',  'type' => 'debit',   'currency' => 'RUB', 'opening_balance' => 5000000,  'color' => '#ffdd2d'],
            ['name' => 'Наличные',      'type' => 'cash',    'currency' => 'RUB', 'opening_balance' => 1500000,  'color' => '#2f9e44'],
            ['name' => 'Накопительный', 'type' => 'savings', 'currency' => 'RUB', 'opening_balance' => 20000000, 'color' => '#1971c2'],
        ];
        foreach ($accounts as $i => $a) {
            $acc = LedAccount::updateOrCreate(
                ['user_id' => $this->userId, 'name' => $a['name']],
                array_merge($a, [
                    'user_id'   => $this->userId,
                    'sort_order' => $i + 1,
                    'opened_at' => $this->now->copy()->subYears(2)->format('Y-m-d'),
                ])
            );
            $this->ledAccounts[$a['name']] = $acc->id;
        }
    }

    private function seedLocations(): void
    {
        $locs = [
            ['name' => 'Дом',       'icon' => 'home'],
            ['name' => 'Работа',    'icon' => 'building'],
            ['name' => 'Гараж',     'icon' => 'car-garage'],
            ['name' => 'Дача',      'icon' => 'tree'],
        ];
        foreach ($locs as $i => $l) {
            $loc = StfLocation::updateOrCreate(
                ['user_id' => $this->userId, 'name' => $l['name']],
                ['user_id' => $this->userId, 'name' => $l['name'], 'sort_order' => $i + 1]
            );
            $this->locations[$l['name']] = $loc->id;
        }
    }

    private function seedThings(): void
    {
        $things = [
            [
                'key'  => 'bmw',
                'data' => [
                    'entity_type'    => 'asset',
                    'name'           => 'BMW 320d E46',
                    'vendor'         => 'BMW',
                    'description'    => 'Дизель 2.0, 150 л.с., 2003 г.в. Цвет: серебристый металлик.',
                    'current_status' => 'active',
                    'purchase_price' => 35000000,
                    'purchase_date'  => $this->now->copy()->subYears(3)->format('Y-m-d'),
                    'track_lifecycle'=> true,
                    'track_location' => true,
                    'serial_no'      => 'WBAET11000F123456',
                ],
            ],
            [
                'key'  => 'laptop',
                'data' => [
                    'entity_type'    => 'asset',
                    'name'           => 'ThinkPad X1 Carbon',
                    'vendor'         => 'Lenovo',
                    'description'    => 'Core i7, 16GB RAM, 512GB SSD. Рабочий ноутбук.',
                    'current_status' => 'active',
                    'purchase_price' => 12000000,
                    'purchase_date'  => $this->now->copy()->subYears(2)->format('Y-m-d'),
                    'track_lifecycle'=> true,
                    'track_location' => false,
                ],
            ],
            [
                'key'  => 'bike',
                'data' => [
                    'entity_type'    => 'asset',
                    'name'           => 'Велосипед Trek',
                    'vendor'         => 'Trek',
                    'description'    => 'Горный, 29 дюймов. Для поездок на работу летом.',
                    'current_status' => 'stored',
                    'purchase_price' => 4500000,
                    'purchase_date'  => $this->now->copy()->subYears(1)->format('Y-m-d'),
                    'track_lifecycle'=> true,
                    'track_location' => true,
                ],
            ],
        ];

        foreach ($things as $t) {
            $thing = StfThing::updateOrCreate(
                ['user_id' => $this->userId, 'name' => $t['data']['name']],
                array_merge($t['data'], ['user_id' => $this->userId])
            );
            $this->things[$t['key']] = $thing->id;
        }
    }

    // ══════════════════════════════════════════════════════════════
    // ИСТОРИЯ МАШИНЫ — ~220 записей Exploiter за 2 года
    // ══════════════════════════════════════════════════════════════

    private function seedCarHistory(): void
    {
        $thingId = $this->things['bmw'];

        // Предопределённые события с реальными ценами
        $events = [
            // 2 года назад
            ['-24 months', 'Покупка BMW 320d', 22, 13, 3500000, 0, 0, 180, 'Взял с рук. Пробег 187000 км. Комплектация полная.'],
            ['-23 months', 'Замена масла двигателя + фильтры', 22, 13, 350000, 0, 90, 0, 'Castrol Edge 5W-30. Масляный + воздушный + салонный фильтры.'],
            ['-23 months', 'Замена тормозной жидкости', 22, 13, 120000, 80000, 0, 60, 'DOT 4. СТО Механика.'],
            ['-22 months', 'Диагностика ходовой', 22, 13, 0, 150000, 0, 60, 'Выявлены: стойки передние, втулки стабилизатора.'],
            ['-22 months', 'Замена стоек передних в сборе', 22, 14, 1400000, 400000, 0, 120, 'Kayaba Excel-G. СТО Механика.'],
            ['-22 months', 'Замена втулок стабилизатора', 22, 13, 80000, 60000, 0, 45, 'Lemförder, перед.'],
            ['-21 months', 'Развал-схождение', 22, 13, 0, 250000, 0, 60, 'После замены стоек. Шиномонтаж у дома.'],
            ['-21 months', 'Страховка ОСАГО', 22, 13, 1200000, 0, 30, 0, 'Альфастрахование. На год.'],
            ['-20 months', 'Зимняя резина + шиномонтаж', 22, 13, 2800000, 120000, 60, 0, 'Continental IceContact 3. 205/55 R16.'],
            ['-19 months', 'Замена лямбда-зонда №1', 22, 14, 650000, 150000, 0, 30, 'Bosch оригинал. Пропала тяга.'],
            ['-18 months', 'Замена свечей зажигания', 22, 13, 180000, 80000, 45, 0, 'NGK Iridium. Комплект 4 шт.'],
            ['-18 months', 'Замена масла КПП', 22, 13, 220000, 80000, 60, 0, 'Castrol MTX 75W-90.'],
            ['-17 months', 'Замена граняты/ШРУС длинного', 22, 14, 380000, 200000, 0, 90, 'GKN оригинал. Хруст при повороте.'],
            ['-16 months', 'Полный антикор днища', 22, 14, 600000, 1400000, 0, 480, 'Кузовной цех Антикор. Мовиль + Динитрол.'],
            ['-15 months', 'Переварка порогов', 22, 14, 400000, 2000000, 0, 960, 'Кузовной цех Антикор. Пороги насквозь.'],
            ['-15 months', 'Переварка задних крыльев', 22, 14, 300000, 1500000, 0, 720, 'Кузовной цех Антикор.'],
            ['-14 months', 'Перекраска по низам и зад', 22, 14, 500000, 1800000, 0, 600, 'Кузовной цех Антикор. В цвет кузова.'],
            ['-13 months', 'Летняя резина + балансировка', 22, 13, 0, 80000, 30, 0, 'Переобулся на летнюю.'],
            ['-13 months', 'Замена масла двигателя', 22, 13, 350000, 0, 60, 0, 'Castrol Edge 5W-30. Пробег 197000 км.'],
            ['-12 months', 'Замена задних пружин', 22, 14, 400000, 200000, 0, 90, 'H&R. Просела задняя часть.'],
            ['-12 months', 'Замена датчика ABS задний левый', 22, 13, 120000, 80000, 0, 30, 'Запчасть с разборки BMW.'],
            ['-11 months', 'ТО плановое', 22, 13, 450000, 0, 90, 0, 'Масло + все фильтры + свечи. Пробег 201000 км.'],
            ['-10 months', 'Замена лобового стекла', 22, 14, 1200000, 300000, 0, 90, 'Pilkington оригинал. Автостёкла 24/7.'],
            ['-10 months', 'Зимняя резина', 22, 13, 0, 80000, 30, 0, 'Переобулся. Колёса хранятся в гараже.'],
            ['-9 months', 'Замена тормозных колодок передних', 22, 14, 380000, 120000, 0, 60, 'Textar. Износ до металла.'],
            ['-9 months', 'Замена тормозных дисков передних', 22, 14, 560000, 160000, 0, 90, 'Zimmermann. В комплекте с колодками.'],
            ['-8 months', 'Страховка ОСАГО', 22, 13, 1350000, 0, 20, 0, 'Ингосстрах. Цена выросла.'],
            ['-7 months', 'Замена ремня ГРМ', 22, 15, 900000, 400000, 0, 180, 'INA комплект: ремень + ролики + помпа. Пробег 207000.'],
            ['-7 months', 'Замена масла двигателя', 22, 13, 350000, 0, 60, 0, 'После замены ГРМ. Castrol Edge.'],
            ['-6 months', 'Летняя резина', 22, 13, 0, 80000, 30, 0, 'Переобулся на летнюю.'],
            ['-5 months', 'Заправка топлива', 22, 13, 320000, 0, 15, 0, 'Лукойл. 55 литров.'],
            ['-4 months', 'Замена охлаждающей жидкости', 22, 13, 180000, 120000, 0, 60, 'BMW Coolant. СТО Механика.'],
            ['-4 months', 'Чистка форсунок', 22, 13, 280000, 200000, 0, 120, 'Ультразвуковая чистка. Дымил при холодном пуске.'],
            ['-3 months', 'Замена масла двигателя', 22, 13, 350000, 0, 60, 0, 'Mobil 1 ESP 5W-30. Пробег 214000 км.'],
            ['-2 months', 'Зимняя резина', 22, 13, 0, 80000, 30, 0, 'Переобулся на зимнюю.'],
            ['-2 months', 'Замена тормозных колодок задних', 22, 14, 280000, 100000, 0, 45, 'Textar.'],
            ['-1 months', 'Диагностика топливной системы', 22, 13, 0, 120000, 0, 60, 'Небольшое падение мощности на холодную.'],

            // Будущее / план
            ['+1 months', 'Развал-схождение',           20, 13, 0, 250000, 0, 60, 'Плановый после зимы.'],
            ['+2 months', 'Летняя резина',               20, 13, 0, 80000, 30, 0, 'Переобуться на летнюю.'],
            ['+2 months', 'ТО плановое',                 20, 13, 450000, 0, 90, 0, 'Масло + фильтры. Пробег ~220000 км.'],
            ['+3 months', 'Замена топливного фильтра',   20, 13, 180000, 80000, 0, 30, 'Давно не менял.'],
            ['+6 months', 'Страховка ОСАГО',             20, 13, 1400000, 0, 20, 0, 'Плановое продление.'],
            ['+8 months', 'Зимняя резина',               20, 13, 0, 80000, 30, 0, 'Переобуться на зимнюю.'],
        ];

        foreach ($events as $e) {
            [$offset, $name, $status, $priority, $partCost, $laborCost, $timeSelf, $timeService, $note] = $e;

            $date = $this->dateFromOffset($offset);

            StfRegister::updateOrCreate(
                ['user_id' => $this->userId, 'thing_id' => $thingId, 'occurred_at' => $date, 'note' => $note],
                [
                    'user_id'          => $this->userId,
                    'thing_id'         => $thingId,
                    'event_type'       => 'repaired',
                    'occurred_at'      => $date,
                    'status'           => $status,
                    'priority'         => $priority,
                    'is_pinned'        => in_array($name, ['Замена ремня ГРМ']) ? 1 : 0,
                    'part_cost'        => $partCost,
                    'labor_cost'       => $laborCost,
                    'time_self_min'    => $timeSelf,
                    'time_service_min' => $timeService,
                    'note'             => $name,
                    'details'          => ['note' => $note],
                ]
            );
        }

        // Заправки — каждые ~10 дней за 2 года (~70 шт)
        $fillDate = $this->now->copy()->subYears(2)->addDays(5);
        while ($fillDate->lt($this->now)) {
            StfRegister::create([
                'user_id'          => $this->userId,
                'thing_id'         => $thingId,
                'event_type'       => 'repaired',
                'occurred_at'      => $fillDate->format('Y-m-d'),
                'status'           => 22, // done
                'priority'         => 13, // normal
                'part_cost'        => rand(280, 380) * 100, // 280-380 руб * 100 (копейки)
                'labor_cost'       => 0,
                'time_self_min'    => rand(10, 20),
                'note'             => 'Заправка',
                'details'          => ['note' => rand(45, 60) . ' л · Лукойл'],
            ]);
            $fillDate->addDays(rand(8, 14));
        }

        $this->command->info('Car history seeded');
    }

    // ══════════════════════════════════════════════════════════════
    // РАБОЧИЕ ДНИ В EVENTOR — ~400 записей за ~2 года
    // ══════════════════════════════════════════════════════════════

    private function seedWorkEvents(): void
    {
        $workTitles = [
            'Рабочий день — %weekday%, %date%',
            'Созвон с командой',
            'Ревью кода',
            'Планёрка',
            'Деплой на прод',
            'Встреча с клиентом',
            'Документация',
            'Bagфикс',
            'Спринт-ретро',
            'Демо продукта',
        ];

        $workNotes = [
            "Начало: 09:00\nКонец: 18:30\n\n## Задачи\n- Ревью PR #142\n- Встреча с командой\n- Документация API\n\n## Итоги\nЗакрыл 3 задачи, задеплоил фикс.",
            "Начало: 10:00\nКонец: 19:00\n\n## Задачи\n- Баг в продакшне\n- Созвон с заказчиком\n\n## Итоги\nБаг пофиксили, заказчик доволен.",
            "Начало: 09:30\nКонец: 18:00\n\n## Задачи\n- Новый модуль\n- Code review\n\n## Итоги\nПродуктивный день.",
            "Начало: 08:00\nКонец: 17:00\n\n## Задачи\n- Рефакторинг\n- Тесты\n\n## Итоги\nПокрытие выросло до 72%.",
        ];

        $sectionId = $this->sections['Работа'];
        $typeId    = $this->evtTypes['Состояние'];
        $meetingId = $this->evtTypes['Встреча'];

        $date = $this->now->copy()->subYears(2);
        $eventCount = 0;

        while ($date->lt($this->now)) {
            $dayOfWeek = $date->dayOfWeek; // 0=Sun, 6=Sat

            // Рабочие дни пн-пт
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $weekdays = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница'];
                $wd = $weekdays[$dayOfWeek - 1];

                // Рабочий день — каждый будний
                EvtEvent::create([
                    'user_id'    => $this->userId,
                    'name'       => "Рабочий день — {$wd}",
                    'type_id'    => $typeId,
                    'section_id' => $sectionId,
                    'content'    => $workNotes[array_rand($workNotes)],
                    'status'     => 2,
                    'access'     => 1,
                    'occurred_at' => $date->format('Y-m-d'),
                ]);
                $eventCount++;

                // Созвон по понедельникам
                if ($dayOfWeek === 1) {
                    EvtEvent::create([
                        'user_id'    => $this->userId,
                        'name'       => 'Планёрка — старт недели',
                        'type_id'    => $meetingId,
                        'section_id' => $sectionId,
                        'content'    => "Обсудили задачи на неделю.\nPriority: завершить текущий спринт.",
                        'status'     => 2,
                        'access'     => 1,
                        'occurred_at' => $date->format('Y-m-d'),
                    ]);
                    $eventCount++;
                }

                // Деплой раз в 2 недели по средам
                if ($dayOfWeek === 3 && $date->weekOfYear % 2 === 0) {
                    EvtEvent::create([
                        'user_id'    => $this->userId,
                        'name'       => 'Деплой на прод',
                        'type_id'    => $this->evtTypes['Задача'],
                        'section_id' => $sectionId,
                        'content'    => "Деплой v" . rand(1, 3) . "." . rand(0, 9) . "." . rand(0, 20) . "\nВсё прошло штатно.",
                        'status'     => 2,
                        'access'     => 1,
                        'occurred_at' => $date->format('Y-m-d'),
                    ]);
                    $eventCount++;
                }
            }

            $date->addDay();
        }

        $this->command->info("Work events seeded: {$eventCount}");
    }

    // ══════════════════════════════════════════════════════════════
    // ЛИЧНЫЕ СОБЫТИЯ — ~80 записей
    // ══════════════════════════════════════════════════════════════

    private function seedPersonalEvents(): void
    {
        $sectionId    = $this->sections['Личное'];
        $healthSection = $this->sections['Здоровье'];
        $typeNote     = $this->evtTypes['Запись'];
        $typeEvent    = $this->evtTypes['Событие'];

        $events = [
            ['-22 months', 'Переезд в новую квартиру', $sectionId, $typeEvent, "Наконец-то! Район хороший, до работы 20 минут пешком."],
            ['-21 months', 'День рождения', $sectionId, $typeEvent, "32 года. Собрались с друзьями, отлично посидели."],
            ['-20 months', 'Поездка в Питер', $sectionId, $typeEvent, "3 дня. Эрмитаж, Петергоф, белые ночи. Обязательно вернусь."],
            ['-18 months', 'Начал бегать по утрам', $healthSection, $typeNote, "Цель — 5 км без остановок. Пока добегаю до 3 км."],
            ['-17 months', 'Пробежал первые 5 км', $healthSection, $typeNote, "25 минут 14 секунд. Личный рекорд!"],
            ['-15 months', 'Отпуск — Турция', $sectionId, $typeEvent, "2 недели. Анталья. Всё включено. Отлично отдохнул."],
            ['-13 months', 'Запись к стоматологу', $healthSection, $typeNote, "Удалил зуб мудрости. Наконец-то."],
            ['-12 months', 'Новый год', $sectionId, $typeEvent, "Встретили дома с семьёй. Оливье, Дед Мороз, бенгальские огни."],
            ['-11 months', 'Подписка на зал', $healthSection, $typeNote, "Тренажёрный зал в 5 минутах от дома. Цена 2500/мес."],
            ['-10 months', 'Купил новый ноутбук', $sectionId, $typeEvent, "ThinkPad X1 Carbon. Рабочий инструмент. Стар уже совсем был."],
            ['-9 months', 'День рождения мамы', $sectionId, $typeEvent, "Подарил цветы и сертификат в спа. Довольна."],
            ['-8 months', 'Конференция DevConf', $sectionId, $typeEvent, "Два дня. Много полезных докладов по архитектуре."],
            ['-7 months', 'Начал учить испанский', $sectionId, $typeNote, "Duolingo + курсы. Цель — читать без словаря."],
            ['-6 months', 'Отпуск — дача', $sectionId, $typeEvent, "Неделя на природе. Шашлыки, рыбалка, тишина."],
            ['-5 months', 'Медосмотр', $healthSection, $typeNote, "Всё в норме. Холестерин немного повышен, врач сказал следить."],
            ['-4 months', 'Велопоездка за город', $sectionId, $typeEvent, "60 км туда-обратно. Тяжело, но здорово."],
            ['-3 months', 'Встреча выпускников', $sectionId, $typeEvent, "10 лет после института. Многих не видел с самого выпуска."],
            ['-2 months', 'Купил велотренажёр', $healthSection, $typeNote, "Домашний. Зимой буду крутить педали не замерзая."],
            ['-1 months', 'Начало нового проекта', $this->sections['Работа'], $typeNote, "Большой проект. Полгода минимум. Команда 5 человек."],
            ['today', 'Сегодня', $sectionId, $typeNote, "Всё идёт по плану."],
        ];

        foreach ($events as $e) {
            [$offset, $name, $section, $type, $content] = $e;
            EvtEvent::create([
                'user_id'    => $this->userId,
                'name'       => $name,
                'type_id'    => $type,
                'section_id' => $section,
                'content'    => $content,
                'status'     => 2,
                'access'     => 1,
                'occurred_at' => $this->dateFromOffset($offset),
            ]);
        }

        $this->command->info('Personal events seeded');
    }

    // ══════════════════════════════════════════════════════════════
    // ТРАНЗАКЦИИ LEDGER — ~300 записей за 2 года
    // ══════════════════════════════════════════════════════════════

    private function seedTransactions(): void
    {
        $card  = $this->ledAccounts['Карта Т-Банк'];
        $cash  = $this->ledAccounts['Наличные'];

        $date = $this->now->copy()->subYears(2)->startOfMonth();

        while ($date->lt($this->now)) {
            $monthKey = $date->format('Y-m');

            // Зарплата 1-го числа
            $this->tx($card, $monthKey, $date->copy()->setDay(1)->format('Y-m-d'),
                rand(148000, 162000) * 100, 'Зарплата ' . $this->monthName($date), $this->ledCats['Доходы/Зарплата']);

            // Аренда 3-го числа
            $this->tx($card, $monthKey, $date->copy()->setDay(3)->format('Y-m-d'),
                -3500000, 'Аренда квартиры', $this->ledCats['Жильё']);

            // Коммуналка
            $this->tx($card, $monthKey, $date->copy()->setDay(5)->format('Y-m-d'),
                -rand(350, 700) * 100, 'Коммунальные услуги', $this->ledCats['Жильё']);

            // Подписки
            $this->tx($card, $monthKey, $date->copy()->setDay(2)->format('Y-m-d'),
                -49900, 'Яндекс Плюс', $this->ledCats['Подписки']);
            $this->tx($card, $monthKey, $date->copy()->setDay(2)->format('Y-m-d'),
                -59900, 'Spotify', $this->ledCats['Подписки']);
            $this->tx($card, $monthKey, $date->copy()->setDay(2)->format('Y-m-d'),
                -29900, 'GitHub', $this->ledCats['Подписки']);

            // Связь
            $this->tx($card, $monthKey, $date->copy()->setDay(4)->format('Y-m-d'),
                -120000, 'МТС Тариф', $this->ledCats['Подписки']);

            // Столовая — каждый рабочий день ~22 раза в месяц
            for ($i = 0; $i < rand(18, 23); $i++) {
                $day = rand(1, 28);
                $this->tx($cash, $monthKey, $date->copy()->setDay($day)->format('Y-m-d'),
                    -rand(240, 320) * 100, 'Столовая', $this->ledCats['Еда/Столовая']);
            }

            // Продукты — 4-6 раз в месяц
            for ($i = 0; $i < rand(4, 6); $i++) {
                $day = rand(1, 28);
                $this->tx($card, $monthKey, $date->copy()->setDay($day)->format('Y-m-d'),
                    -rand(1200, 3500) * 100, 'Продукты ' . rand(1, 4) . ' чел', $this->ledCats['Еда/Продукты']);
            }

            // Кафе/рестораны — 2-4 раза
            for ($i = 0; $i < rand(2, 4); $i++) {
                $day = rand(1, 28);
                $this->tx($card, $monthKey, $date->copy()->setDay($day)->format('Y-m-d'),
                    -rand(800, 2500) * 100, 'Кафе', $this->ledCats['Еда/Кафе']);
            }

            // Транспорт
            for ($i = 0; $i < rand(3, 8); $i++) {
                $day = rand(1, 28);
                $this->tx($card, $monthKey, $date->copy()->setDay($day)->format('Y-m-d'),
                    -rand(50, 400) * 100, 'Яндекс Такси', $this->ledCats['Транспорт']);
            }

            // Заправки — уже в Exploiter, но для Ledger тоже
            for ($i = 0; $i < rand(2, 4); $i++) {
                $day = rand(1, 28);
                $this->tx($card, $monthKey, $date->copy()->setDay($day)->format('Y-m-d'),
                    -rand(2800, 3800) * 100, 'Заправка', $this->ledCats['Авто/Топливо']);
            }

            // Одежда — раз в 2-3 месяца
            if (rand(1, 3) === 1) {
                $this->tx($card, $monthKey, $date->copy()->setDay(rand(10, 25))->format('Y-m-d'),
                    -rand(2000, 8000) * 100, 'Одежда', $this->ledCats['Одежда']);
            }

            // Здоровье — тренажёрный зал ежемесячно
            $this->tx($card, $monthKey, $date->copy()->setDay(1)->format('Y-m-d'),
                -250000, 'Абонемент зал', $this->ledCats['Здоровье']);

            // Фриланс иногда
            if (rand(1, 4) === 1) {
                $this->tx($card, $monthKey, $date->copy()->setDay(rand(10, 28))->format('Y-m-d'),
                    rand(20000, 60000) * 100, 'Фриланс-проект', $this->ledCats['Доходы/Фриланс']);
            }

            $date->addMonth();
        }

        $this->command->info('Ledger transactions seeded');
    }

    // ══════════════════════════════════════════════════════════════
    // ХЕЛПЕРЫ
    // ══════════════════════════════════════════════════════════════

    private function dateFromOffset(string $offset): string
    {
        if ($offset === 'today') return $this->now->format('Y-m-d');

        // +2 months, -3 months, +1 months etc.
        preg_match('/([+-])(\d+)\s+(\w+)/', $offset, $m);
        if (!$m) return $this->now->format('Y-m-d');

        [$, $sign, $amount, $unit] = $m;
        $date = $this->now->copy();
        $amount = (int)$amount;

        if ($sign === '-') {
            match($unit) {
                'months' => $date->subMonths($amount)->addDays(rand(0, 10)),
                'days'   => $date->subDays($amount),
                default  => null,
            };
        } else {
            match($unit) {
                'months' => $date->addMonths($amount)->addDays(rand(0, 10)),
                'days'   => $date->addDays($amount),
                default  => null,
            };
        }

        return $date->format('Y-m-d');
    }

    private function tx(string $accountId, string $monthKey, string $date, int $amount, string $title, string $categoryId): void
    {
        LedTransaction::create([
            'user_id'     => $this->userId,
            'account_id'  => $accountId,
            'category_id' => $categoryId,
            'amount'      => $amount,
            'title'       => $title,
            'occurred_at' => $date,
            'month_key'   => $monthKey,
            'flow_kind'   => $amount > 0 ? 'income' : 'expense',
            'status'      => 1,
            'is_disabled' => false,
            'sort_order'  => 0,
        ]);
    }

    private function monthName(Carbon $date): string
    {
        $months = ['Январь','Февраль','Март','Апрель','Май','Июнь',
                   'Июль','Август','Сентябрь','Октябрь','Ноябрь','Декабрь'];
        return $months[$date->month - 1];
    }
}
