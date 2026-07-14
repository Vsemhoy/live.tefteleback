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
use App\Http\Controllers\Exploiter\ExploiterController;
use App\Http\Controllers\Auth\DemoAuthController;
use App\Http\Controllers\Demo\DemoMaintenanceController;
use App\Http\Controllers\Contactor\ContactorController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/demo', [DemoAuthController::class, 'login']);
Route::post('/demo/cleanup', [DemoMaintenanceController::class, 'cleanup']);
Route::post('/auth/logout', [AuthController::class, 'logout']);

Route::post('/auth/signup', [AuthController::class, 'signup']);

Route::middleware('auth.jwt')->group(function () {
    Route::post('/auth/me', [AuthController::class, 'me']);
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
    return response()->json(['message' => 'API Ñ€Ð°Ð±Ð¾Ñ‚Ð°ÐµÑ‚!']);
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
    Route::get('/contactor/contacts', [ContactorController::class, 'contacts']);
    Route::post('/contactor/contacts', [ContactorController::class, 'storeContact']);
    Route::get('/contactor/contacts/{id}', [ContactorController::class, 'showContact']);
    Route::put('/contactor/contacts/{id}', [ContactorController::class, 'updateContact']);
    Route::delete('/contactor/contacts/{id}', [ContactorController::class, 'destroyContact']);

    Route::get('/contactor/contents', [ContactorController::class, 'contents']);
    Route::post('/contactor/contents', [ContactorController::class, 'storeContent']);
    Route::put('/contactor/contents/{id}', [ContactorController::class, 'updateContent']);
    Route::delete('/contactor/contents/{id}', [ContactorController::class, 'destroyContent']);

    Route::get('/contactor/relations', [ContactorController::class, 'relations']);
    Route::post('/contactor/relations', [ContactorController::class, 'storeRelation']);
    Route::put('/contactor/relations/{id}', [ContactorController::class, 'updateRelation']);
    Route::delete('/contactor/relations/{id}', [ContactorController::class, 'destroyRelation']);

    Route::get('/exploiter/things', [ExploiterController::class, 'things']);
    Route::get('/exploiter/events', [ExploiterController::class, 'index']);
    Route::get('/exploiter/stats/{thingId}', [ExploiterController::class, 'stats']);
    Route::post('/exploiter/events', [ExploiterController::class, 'store']);
    Route::get('/exploiter/events/{id}', [ExploiterController::class, 'show']);
    Route::put('/exploiter/events/{id}', [ExploiterController::class, 'update']);
    Route::delete('/exploiter/events/{id}', [ExploiterController::class, 'destroy']);
    Route::post('/exploiter/events/{id}/pin', [ExploiterController::class, 'togglePin']);

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
        // â”€â”€ Accounts â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::get   ('/ledger/accounts',           [LedgerAccountController::class, 'index']);
        Route::post  ('/ledger/accounts',           [LedgerAccountController::class, 'store']);
        Route::put   ('/ledger/accounts/{id}',      [LedgerAccountController::class, 'update']);
        Route::delete('/ledger/accounts/{id}',      [LedgerAccountController::class, 'destroy']);
        

        // â”€â”€ Transactions â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::get   ('/ledger/transactions',             [LedgerTransactionController::class, 'index']);
        Route::post  ('/ledger/transactions',             [LedgerTransactionController::class, 'store']);
        Route::get   ('/ledger/transactions/{id}',        [LedgerTransactionController::class, 'show']);
        Route::put   ('/ledger/transactions/{id}',        [LedgerTransactionController::class, 'update']);
        Route::delete('/ledger/transactions/{id}',        [LedgerTransactionController::class, 'destroy']);
        Route::patch ('/ledger/transactions/{id}/move',   [LedgerTransactionController::class, 'move']);


        Route::patch ('/ledger/transactions/{id}/toggledisabled',  [LedgerTransactionController::class, 'toggleDisabled']);

        
        // â”€â”€ Categories â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::post('/ledger/categories/reorder',   [LedgerCategoryController::class, 'reorder']);
        Route::get   ('/ledger/categories',         [LedgerCategoryController::class, 'index']);
        Route::post  ('/ledger/categories',         [LedgerCategoryController::class, 'store']);
        Route::put   ('/ledger/categories/{id}',    [LedgerCategoryController::class, 'update']);
        Route::delete('/ledger/categories/{id}',    [LedgerCategoryController::class, 'destroy']);


        // â”€â”€ Groups â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::get   ('/ledger/groups',             [LedgerGroupController::class, 'index']);
        Route::post  ('/ledger/groups',             [LedgerGroupController::class, 'store']);
        Route::put   ('/ledger/groups/{id}',        [LedgerGroupController::class, 'update']);
        Route::patch ('/ledger/groups/{id}/toggle', [LedgerGroupController::class, 'toggle']);
        Route::delete('/ledger/groups/{id}',        [LedgerGroupController::class, 'destroy']);

        // â”€â”€ Month Totals â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
        Route::get('/ledger/month-totals', [LedgerMonthTotalsController::class, 'index']);
    // });
});

Route::prefix('stuffer')->middleware('auth.jwt')->group(function () {

    // â”€â”€ Ð›Ð¾ÐºÐ°Ñ†Ð¸Ð¸ â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get   ('locations',          [StufferLocationController::class, 'index']);
    Route::post  ('locations',          [StufferLocationController::class, 'store']);
    Route::post  ('locations/reorder',  [StufferLocationController::class, 'reorder']);
    Route::put   ('locations/{id}',     [StufferLocationController::class, 'update']);
    Route::delete('locations/{id}',     [StufferLocationController::class, 'destroy']);

    // â”€â”€ Ð’ÐµÑ‰Ð¸ â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get   ('things',             [StufferThingController::class, 'index']);
    Route::post  ('things',             [StufferThingController::class, 'store']);
    Route::get   ('things/{id}',        [StufferThingController::class, 'show']);
    Route::put   ('things/{id}',        [StufferThingController::class, 'update']);
    Route::delete('things/{id}',        [StufferThingController::class, 'destroy']);
    Route::post  ('things/{id}/open',   [StufferThingController::class, 'open']);

    // â”€â”€ Ð ÐµÐ³Ð¸ÑÑ‚Ñ€ ÑÐ¾Ð±Ñ‹Ñ‚Ð¸Ð¹ â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get   ('register',           [StufferRegisterController::class, 'index']);
    Route::post  ('register',           [StufferRegisterController::class, 'store']);
    Route::delete('register/{id}',      [StufferRegisterController::class, 'destroy']);

    // â”€â”€ Ð Ð°ÑÑ…Ð¾Ð´Ñ‹ â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    Route::get   ('expenses',           [StufferExpenseController::class, 'index']);
    Route::post  ('expenses',           [StufferExpenseController::class, 'store']);
    Route::delete('expenses/{id}',      [StufferExpenseController::class, 'destroy']);

    // â”€â”€ ÐšÐ°Ñ‚ÐµÐ³Ð¾Ñ€Ð¸Ð¸ â€” Ð¸ÑÐ¿Ð¾Ð»ÑŒÐ·ÑƒÐµÐ¼ Ð¾Ð±Ñ‰Ð¸Ð¹ Ledger ÐºÐ¾Ð½Ñ‚Ñ€Ð¾Ð»Ð»ÐµÑ€ â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
    // GET /ledger/categories ÑƒÐ¶Ðµ ÑÑƒÑ‰ÐµÑÑ‚Ð²ÑƒÐµÑ‚ Ð¸ Ð²Ð¾Ð·Ð²Ñ€Ð°Ñ‰Ð°ÐµÑ‚ led_categories
    // Stuffer Ð¿Ñ€Ð¾ÑÑ‚Ð¾ Ñ‡Ð¸Ñ‚Ð°ÐµÑ‚ Ñ‚Ðµ Ð¶Ðµ Ð´Ð°Ð½Ð½Ñ‹Ðµ â€” Ð¾Ñ‚Ð´ÐµÐ»ÑŒÐ½Ð¾Ð³Ð¾ Ñ€Ð¾ÑƒÑ‚Ð° Ð½Ðµ Ð½ÑƒÐ¶Ð½Ð¾
});

