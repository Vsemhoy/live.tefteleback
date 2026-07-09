<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Eventor\EventorApiController;
use App\Http\Controllers\Staffer\StufferLocationController;
use App\Http\Controllers\Staffer\StufferRegisterController;
use App\Http\Controllers\Staffer\StufferThingController;
use App\Http\Controllers\Staffer\StufferExpenseController;
use App\Models\User;
use App\Models\LedCategory;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ledger\LedgerAccountController;
use App\Http\Controllers\Ledger\LedgerGroupController;
use App\Http\Controllers\Ledger\LedgerMonthTotalsController;
use App\Http\Controllers\Ledger\LedgerTransactionController;
use App\Http\Controllers\Ledger\LedgerCategoryController;
use App\Http\Controllers\System\TemplateController;
use App\Http\Controllers\Feed\FeedController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::post('/auth/signup', [AuthController::class, 'signup']);

Route::middleware('auth.jwt')->group(function () {
    Route::post('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/validate', [AuthController::class, 'validate']);
    Route::post('/auth/repass', [AuthController::class, 'changePass']);
    Route::post('/auth/refresh', [AuthController::class, 'refresh']);
});

Route::get('/test', function () {
    // $user = new User();
    // $user->name = 'Test User';
    // $user->email = 'test@example.com';
    // $user->password = bcrypt('password123');
    // $user->save();
    return response()->json(['message' => 'API работает!']);
});



Route::get('/cors-test', function () {
    return response('CORS is working', 200);
});

Route::middleware('auth.jwt')->group(function () {
    Route::get('/wakeupd', function () {
        return 'I run!';
    });
});


Route::get('/opn/eventor/e/{id}', [EventorApiController::class, 'getEventPublicAction']);


Route::middleware('auth.jwt')->group(function () {


    Route::get('/feed', [FeedController::class, 'index']);

    Route::post('/eventor/getmyevents', [EventorApiController::class, 'getMyEventsAction']);
    Route::post('/eventor/getpinned ', [EventorApiController::class, 'getMyPinnedAction']);
    Route::post('/eventor/getmyevent/{id}', [EventorApiController::class, 'getMyEventAction']);
    Route::post('/eventor/getmysections', [EventorApiController::class, 'getMySections']);
    Route::post('/eventor/getmycategories', [EventorApiController::class, 'getMyCategories']);
    Route::post('/eventor/getmytypes', [EventorApiController::class, 'getMyTypes']);
    Route::post('/eventor/saveevent', [EventorApiController::class, 'saveEventAction']);
    Route::post('/eventor/savesection', [EventorApiController::class, 'saveSectionAction']);
    Route::post('/eventor/updateevent/{id}', [EventorApiController::class, 'updateEventAction']);
    Route::post('/eventor/togglepinned/{id}', [EventorApiController::class, 'togglePinnedEventAction']);
    Route::post('/eventor/updatesection/{id}', [EventorApiController::class, 'updateSectionAction']);
    Route::delete('/eventor/deletesection/{id}', [EventorApiController::class, 'deleteSectionAction']);
    Route::post('/eventor/reordersections', [EventorApiController::class, 'reorderSectionsAction']);
    Route::post('/eventor/search', [EventorApiController::class, 'search']);
    Route::delete('/eventor/deleteevent/{id}', [EventorApiController::class, 'deleteEvent']);
    Route::post('/eventor/getmytags', [EventorApiController::class, 'getMyTagsAction']);
    Route::post('/eventor/savetag', [EventorApiController::class, 'saveTagAction']);
    Route::post('/eventor/updatetag/{id}', [EventorApiController::class, 'updateTagAction']);
    Route::delete('/eventor/deletetag/{id}', [EventorApiController::class, 'deleteTagAction']);

    Route::get('/templates', [TemplateController::class, 'index']);
    Route::post('/templates', [TemplateController::class, 'store']);
    Route::put('/templates/{id}', [TemplateController::class, 'update']);
    Route::delete('/templates/{id}', [TemplateController::class, 'destroy']);

    // Route::prefix('ledger')->group(function () {
        // ── Accounts ─────────────────────────────────────────────────
        Route::get   ('/ledger/accounts',           [LedgerAccountController::class, 'index']);
        Route::post  ('/ledger/accounts',           [LedgerAccountController::class, 'store']);
        Route::put   ('/ledger/accounts/{id}',      [LedgerAccountController::class, 'update']);
        Route::delete('/ledger/accounts/{id}',      [LedgerAccountController::class, 'destroy']);
        

        // ── Transactions ──────────────────────────────────────────────
        Route::get   ('/ledger/transactions',             [LedgerTransactionController::class, 'index']);
        Route::post  ('/ledger/transactions',             [LedgerTransactionController::class, 'store']);
        Route::get   ('/ledger/transactions/{id}',        [LedgerTransactionController::class, 'show']);
        Route::put   ('/ledger/transactions/{id}',        [LedgerTransactionController::class, 'update']);
        Route::delete('/ledger/transactions/{id}',        [LedgerTransactionController::class, 'destroy']);
        Route::patch ('/ledger/transactions/{id}/move',   [LedgerTransactionController::class, 'move']);


        Route::patch ('/ledger/transactions/{id}/toggledisabled',  [LedgerTransactionController::class, 'toggleDisabled']);

        
        // ── Categories ────────────────────────────────────────────────
        Route::post('/ledger/categories/reorder',   [LedgerCategoryController::class, 'reorder']);
        Route::get   ('/ledger/categories',         [LedgerCategoryController::class, 'index']);
        Route::post  ('/ledger/categories',         [LedgerCategoryController::class, 'store']);
        Route::put   ('/ledger/categories/{id}',    [LedgerCategoryController::class, 'update']);
        Route::delete('/ledger/categories/{id}',    [LedgerCategoryController::class, 'destroy']);


        // ── Groups ────────────────────────────────────────────────────
        Route::get   ('/ledger/groups',             [LedgerGroupController::class, 'index']);
        Route::post  ('/ledger/groups',             [LedgerGroupController::class, 'store']);
        Route::put   ('/ledger/groups/{id}',        [LedgerGroupController::class, 'update']);
        Route::patch ('/ledger/groups/{id}/toggle', [LedgerGroupController::class, 'toggle']);
        Route::delete('/ledger/groups/{id}',        [LedgerGroupController::class, 'destroy']);

        // ── Month Totals ──────────────────────────────────────────────
        Route::get('/ledger/month-totals', [LedgerMonthTotalsController::class, 'index']);
    // });
});

Route::prefix('stuffer')->middleware('auth.jwt')->group(function () {

    // ── Локации ───────────────────────────────────────────────────
    Route::get   ('locations',          [StufferLocationController::class, 'index']);
    Route::post  ('locations',          [StufferLocationController::class, 'store']);
    Route::post  ('locations/reorder',  [StufferLocationController::class, 'reorder']);
    Route::put   ('locations/{id}',     [StufferLocationController::class, 'update']);
    Route::delete('locations/{id}',     [StufferLocationController::class, 'destroy']);

    // ── Вещи ──────────────────────────────────────────────────────
    Route::get   ('things',             [StufferThingController::class, 'index']);
    Route::post  ('things',             [StufferThingController::class, 'store']);
    Route::get   ('things/{id}',        [StufferThingController::class, 'show']);
    Route::put   ('things/{id}',        [StufferThingController::class, 'update']);
    Route::delete('things/{id}',        [StufferThingController::class, 'destroy']);
    Route::post  ('things/{id}/open',   [StufferThingController::class, 'open']);

    // ── Регистр событий ───────────────────────────────────────────
    Route::get   ('register',           [StufferRegisterController::class, 'index']);
    Route::post  ('register',           [StufferRegisterController::class, 'store']);
    Route::delete('register/{id}',      [StufferRegisterController::class, 'destroy']);

    // ── Расходы ───────────────────────────────────────────────────
    Route::get   ('expenses',           [StufferExpenseController::class, 'index']);
    Route::post  ('expenses',           [StufferExpenseController::class, 'store']);
    Route::delete('expenses/{id}',      [StufferExpenseController::class, 'destroy']);

    // ── Категории — используем общий Ledger контроллер ────────────
    // GET /ledger/categories уже существует и возвращает led_categories
    // Stuffer просто читает те же данные — отдельного роута не нужно
});
