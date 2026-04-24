<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Eventor\EventorApiController;
use App\Http\Controllers\Staffer\StufferLocationController;
use App\Http\Controllers\Staffer\StufferRegisterController;
use App\Http\Controllers\Staffer\StufferThingController;
use App\Http\Controllers\Staffer\StufferExpenseController;
use App\Models\User;
use App\Models\BudCategory;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Badger\BadgerAccountController;
use App\Http\Controllers\Badger\BadgerGroupController;
use App\Http\Controllers\Badger\BadgerMonthTotalsController;
use App\Http\Controllers\Badger\BadgerTransactionController;
use App\Http\Controllers\Badger\BadgerCategoryController;

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

// Route::options('/{any}', function () {
//     return response()->json([], 204)
//         ->header('Access-Control-Allow-Origin', 'http://localhost:3002')
//         ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE')
//         ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
// })->where('any', '.*');

Route::get('/cors-test', function () {
    return response('CORS is working', 200);
});

Route::middleware('auth.jwt')->group(function () {
    Route::get('/wakeupd', function () {
        return 'I run!';
    });
});

// Route::options('/{any}', function () use ($allowedOrigins) {
//     $origin = request()->header('Origin');

//     $response = response()->noContent();

//     if (in_array($origin, $allowedOrigins)) {
//         $response->headers->set('Access-Control-Allow-Origin', $origin);
//         $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
//         $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
//         $response->headers->set('Access-Control-Allow-Credentials', 'true');
//     }

//     return $response;
// })->where('any', '.*');

Route::middleware('auth.jwt')->group(function () {
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

    // Route::prefix('badger')->group(function () {
        // ── Accounts ─────────────────────────────────────────────────
        Route::get   ('/badger/accounts',           [BadgerAccountController::class, 'index']);
        Route::post  ('/badger/accounts',           [BadgerAccountController::class, 'store']);
        Route::put   ('/badger/accounts/{id}',      [BadgerAccountController::class, 'update']);
        Route::delete('/badger/accounts/{id}',      [BadgerAccountController::class, 'destroy']);
        

        // ── Transactions ──────────────────────────────────────────────
        Route::get   ('/badger/transactions',             [BadgerTransactionController::class, 'index']);
        Route::post  ('/badger/transactions',             [BadgerTransactionController::class, 'store']);
        Route::get   ('/badger/transactions/{id}',        [BadgerTransactionController::class, 'show']);
        Route::put   ('/badger/transactions/{id}',        [BadgerTransactionController::class, 'update']);
        Route::delete('/badger/transactions/{id}',        [BadgerTransactionController::class, 'destroy']);
        Route::patch ('/badger/transactions/{id}/move',   [BadgerTransactionController::class, 'move']);


        Route::patch ('/badger/transactions/{id}/toggledisabled',  [BadgerTransactionController::class, 'toggleDisabled']);

        
        // ── Categories ────────────────────────────────────────────────
        Route::post('/badger/categories/reorder',   [BadgerCategoryController::class, 'reorder']);
        Route::get   ('/badger/categories',         [BadgerCategoryController::class, 'index']);
        Route::post  ('/badger/categories',         [BadgerCategoryController::class, 'store']);
        Route::put   ('/badger/categories/{id}',    [BadgerCategoryController::class, 'update']);
        Route::delete('/badger/categories/{id}',    [BadgerCategoryController::class, 'destroy']);


        // ── Groups ────────────────────────────────────────────────────
        Route::get   ('/badger/groups',             [BadgerGroupController::class, 'index']);
        Route::post  ('/badger/groups',             [BadgerGroupController::class, 'store']);
        Route::put   ('/badger/groups/{id}',        [BadgerGroupController::class, 'update']);
        Route::patch ('/badger/groups/{id}/toggle', [BadgerGroupController::class, 'toggle']);
        Route::delete('/badger/groups/{id}',        [BadgerGroupController::class, 'destroy']);

        // ── Month Totals ──────────────────────────────────────────────
        Route::get('/badger/month-totals', [BadgerMonthTotalsController::class, 'index']);
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

    // ── Категории — используем общий Badger контроллер ────────────
    // GET /badger/categories уже существует и возвращает bud_categories
    // Stuffer просто читает те же данные — отдельного роута не нужно
});
