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
use App\Http\Controllers\Factor\FactorController;
use App\Http\Controllers\Tasker\TaskerController;
use App\Http\Controllers\Projector\ProjectorController;
use App\Http\Controllers\System\TimerController;

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
    return response()->json(['message' => 'API Ã‘â‚¬ÃÂ°ÃÂ±ÃÂ¾Ã‘â€šÃÂ°ÃÂµÃ‘â€š!']);
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
    Route::get('/timer/active', [TimerController::class, 'active']);
    Route::get('/timer/entries', [TimerController::class, 'entries']);
    Route::post('/timer/entries', [TimerController::class, 'storeEntry']);
    Route::put('/timer/entries/{id}', [TimerController::class, 'updateEntry']);
    Route::delete('/timer/entries/{id}', [TimerController::class, 'destroyEntry']);
    Route::post('/timer/start', [TimerController::class, 'start']);
    Route::post('/timer/stop', [TimerController::class, 'stop']);
    Route::post('/timer/report', [TimerController::class, 'report']);

    Route::get('/projector/projects', [ProjectorController::class, 'index']);
    Route::post('/projector/projects', [ProjectorController::class, 'store']);
    Route::get('/projector/projects/{id}', [ProjectorController::class, 'show']);
    Route::put('/projector/projects/{id}', [ProjectorController::class, 'update']);
    Route::delete('/projector/projects/{id}', [ProjectorController::class, 'destroy']);

    Route::get('/tasker/tasks', [TaskerController::class, 'index']);
    Route::post('/tasker/tasks', [TaskerController::class, 'store']);
    Route::get('/tasker/tasks/{id}', [TaskerController::class, 'show']);
    Route::put('/tasker/tasks/{id}', [TaskerController::class, 'update']);
    Route::delete('/tasker/tasks/{id}', [TaskerController::class, 'destroy']);

    Route::get('/tasker/logs', [TaskerController::class, 'logs']);
    Route::post('/tasker/logs', [TaskerController::class, 'storeLog']);
    Route::put('/tasker/logs/{id}', [TaskerController::class, 'updateLog']);
    Route::delete('/tasker/logs/{id}', [TaskerController::class, 'destroyLog']);

    Route::get('/tasker/blockers', [TaskerController::class, 'blockers']);
    Route::post('/tasker/blockers', [TaskerController::class, 'storeBlocker']);
    Route::put('/tasker/blockers/{id}', [TaskerController::class, 'updateBlocker']);
    Route::delete('/tasker/blockers/{id}', [TaskerController::class, 'destroyBlocker']);

    Route::get('/factor/facts', [FactorController::class, 'index']);
    Route::post('/factor/facts', [FactorController::class, 'store']);
    Route::get('/factor/facts/{id}', [FactorController::class, 'show']);
    Route::put('/factor/facts/{id}', [FactorController::class, 'update']);
    Route::delete('/factor/facts/{id}', [FactorController::class, 'destroy']);
    Route::post('/factor/facts/{id}/pin', [FactorController::class, 'togglePin']);
    Route::get('/contactor/contacts', [ContactorController::class, 'contacts']);
    Route::post('/contactor/contacts', [ContactorController::class, 'storeContact']);
    Route::get('/contactor/contacts/{id}', [ContactorController::class, 'showContact']);
    Route::put('/contactor/contacts/{id}', [ContactorController::class, 'updateContact']);
    Route::delete('/contactor/contacts/{id}', [ContactorController::class, 'destroyContact']);

    Route::get('/contactor/contents', [ContactorController::class, 'contents']);
    Route::post('/contactor/contents', [ContactorController::class, 'storeContent']);
    Route::put('/contactor/contents/{id}', [ContactorController::class, 'updateContent']);
    Route::delete('/contactor/contents/{id}', [ContactorController::class, 'destroyContent']);
    Route::get('/contactor/logs', [ContactorController::class, 'contents']);
    Route::post('/contactor/logs', [ContactorController::class, 'storeContent']);
    Route::put('/contactor/logs/{id}', [ContactorController::class, 'updateContent']);
    Route::delete('/contactor/logs/{id}', [ContactorController::class, 'destroyContent']);

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
    Route::post('/exploiter/events/{id}/eventor', [ExploiterController::class, 'createEventor']);

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
        // Ã¢â€â‚¬Ã¢â€â‚¬ Accounts Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
        Route::get   ('/ledger/accounts',           [LedgerAccountController::class, 'index']);
        Route::post  ('/ledger/accounts',           [LedgerAccountController::class, 'store']);
        Route::put   ('/ledger/accounts/{id}',      [LedgerAccountController::class, 'update']);
        Route::delete('/ledger/accounts/{id}',      [LedgerAccountController::class, 'destroy']);
        

        // Ã¢â€â‚¬Ã¢â€â‚¬ Transactions Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
        Route::get   ('/ledger/transactions',             [LedgerTransactionController::class, 'index']);
        Route::post  ('/ledger/transactions',             [LedgerTransactionController::class, 'store']);
        Route::get   ('/ledger/transactions/{id}',        [LedgerTransactionController::class, 'show']);
        Route::put   ('/ledger/transactions/{id}',        [LedgerTransactionController::class, 'update']);
        Route::delete('/ledger/transactions/{id}',        [LedgerTransactionController::class, 'destroy']);
        Route::patch ('/ledger/transactions/{id}/move',   [LedgerTransactionController::class, 'move']);


        Route::patch ('/ledger/transactions/{id}/toggledisabled',  [LedgerTransactionController::class, 'toggleDisabled']);

        
        // Ã¢â€â‚¬Ã¢â€â‚¬ Categories Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
        Route::post('/ledger/categories/reorder',   [LedgerCategoryController::class, 'reorder']);
        Route::get   ('/ledger/categories',         [LedgerCategoryController::class, 'index']);
        Route::post  ('/ledger/categories',         [LedgerCategoryController::class, 'store']);
        Route::put   ('/ledger/categories/{id}',    [LedgerCategoryController::class, 'update']);
        Route::delete('/ledger/categories/{id}',    [LedgerCategoryController::class, 'destroy']);


        // Ã¢â€â‚¬Ã¢â€â‚¬ Groups Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
        Route::get   ('/ledger/groups',             [LedgerGroupController::class, 'index']);
        Route::post  ('/ledger/groups',             [LedgerGroupController::class, 'store']);
        Route::put   ('/ledger/groups/{id}',        [LedgerGroupController::class, 'update']);
        Route::patch ('/ledger/groups/{id}/toggle', [LedgerGroupController::class, 'toggle']);
        Route::delete('/ledger/groups/{id}',        [LedgerGroupController::class, 'destroy']);

        // Ã¢â€â‚¬Ã¢â€â‚¬ Month Totals Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
        Route::get('/ledger/month-totals', [LedgerMonthTotalsController::class, 'index']);
    // });
});

Route::prefix('stuffer')->middleware('auth.jwt')->group(function () {

    // Ã¢â€â‚¬Ã¢â€â‚¬ Ãâ€ºÃÂ¾ÃÂºÃÂ°Ã‘â€ ÃÂ¸ÃÂ¸ Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    Route::get   ('locations',          [StufferLocationController::class, 'index']);
    Route::post  ('locations',          [StufferLocationController::class, 'store']);
    Route::post  ('locations/reorder',  [StufferLocationController::class, 'reorder']);
    Route::put   ('locations/{id}',     [StufferLocationController::class, 'update']);
    Route::delete('locations/{id}',     [StufferLocationController::class, 'destroy']);

    // Ã¢â€â‚¬Ã¢â€â‚¬ Ãâ€™ÃÂµÃ‘â€°ÃÂ¸ Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    Route::get   ('things',             [StufferThingController::class, 'index']);
    Route::post  ('things',             [StufferThingController::class, 'store']);
    Route::get   ('things/{id}',        [StufferThingController::class, 'show']);
    Route::put   ('things/{id}',        [StufferThingController::class, 'update']);
    Route::delete('things/{id}',        [StufferThingController::class, 'destroy']);
    Route::post  ('things/{id}/open',   [StufferThingController::class, 'open']);

    // Ã¢â€â‚¬Ã¢â€â‚¬ ÃÂ ÃÂµÃÂ³ÃÂ¸Ã‘ÂÃ‘â€šÃ‘â‚¬ Ã‘ÂÃÂ¾ÃÂ±Ã‘â€¹Ã‘â€šÃÂ¸ÃÂ¹ Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    Route::get   ('register',           [StufferRegisterController::class, 'index']);
    Route::post  ('register',           [StufferRegisterController::class, 'store']);
    Route::delete('register/{id}',      [StufferRegisterController::class, 'destroy']);

    // Ã¢â€â‚¬Ã¢â€â‚¬ ÃÂ ÃÂ°Ã‘ÂÃ‘â€¦ÃÂ¾ÃÂ´Ã‘â€¹ Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    Route::get   ('expenses',           [StufferExpenseController::class, 'index']);
    Route::post  ('expenses',           [StufferExpenseController::class, 'store']);
    Route::delete('expenses/{id}',      [StufferExpenseController::class, 'destroy']);

    // Ã¢â€â‚¬Ã¢â€â‚¬ ÃÅ¡ÃÂ°Ã‘â€šÃÂµÃÂ³ÃÂ¾Ã‘â‚¬ÃÂ¸ÃÂ¸ Ã¢â‚¬â€ ÃÂ¸Ã‘ÂÃÂ¿ÃÂ¾ÃÂ»Ã‘Å’ÃÂ·Ã‘Æ’ÃÂµÃÂ¼ ÃÂ¾ÃÂ±Ã‘â€°ÃÂ¸ÃÂ¹ Ledger ÃÂºÃÂ¾ÃÂ½Ã‘â€šÃ‘â‚¬ÃÂ¾ÃÂ»ÃÂ»ÃÂµÃ‘â‚¬ Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬Ã¢â€â‚¬
    // GET /ledger/categories Ã‘Æ’ÃÂ¶ÃÂµ Ã‘ÂÃ‘Æ’Ã‘â€°ÃÂµÃ‘ÂÃ‘â€šÃÂ²Ã‘Æ’ÃÂµÃ‘â€š ÃÂ¸ ÃÂ²ÃÂ¾ÃÂ·ÃÂ²Ã‘â‚¬ÃÂ°Ã‘â€°ÃÂ°ÃÂµÃ‘â€š led_categories
    // Stuffer ÃÂ¿Ã‘â‚¬ÃÂ¾Ã‘ÂÃ‘â€šÃÂ¾ Ã‘â€¡ÃÂ¸Ã‘â€šÃÂ°ÃÂµÃ‘â€š Ã‘â€šÃÂµ ÃÂ¶ÃÂµ ÃÂ´ÃÂ°ÃÂ½ÃÂ½Ã‘â€¹ÃÂµ Ã¢â‚¬â€ ÃÂ¾Ã‘â€šÃÂ´ÃÂµÃÂ»Ã‘Å’ÃÂ½ÃÂ¾ÃÂ³ÃÂ¾ Ã‘â‚¬ÃÂ¾Ã‘Æ’Ã‘â€šÃÂ° ÃÂ½ÃÂµ ÃÂ½Ã‘Æ’ÃÂ¶ÃÂ½ÃÂ¾
});



