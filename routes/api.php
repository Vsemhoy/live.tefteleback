<?php


use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Eventor\EventorApiController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::post('/auth/signup', [AuthController::class, 'signup']);


Route::middleware('auth:api')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
});





Route::middleware('auth.jwt')->group(function () {
    Route::post('/auth/me', [AuthController::class, 'me']);
});

Route::middleware('auth.jwt')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/validate', [AuthController::class, 'validate']);
    Route::post('/auth/repass', [AuthController::class, 'changePass']);
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
    Route::get('/wakeupd', function(){
        return "I run!";
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
    Route::post('/eventor/getmyevent/{id}', [EventorApiController::class, 'getMyEventAction']);
    Route::post('/eventor/getmysections', [EventorApiController::class, 'getMySections']);
    Route::post('/eventor/getmycategories', [EventorApiController::class, 'getMyCategories']);
    Route::post('/eventor/getmytypes', [EventorApiController::class, 'getMyTypes']);
    Route::post('/eventor/saveevent', [EventorApiController::class, 'saveEventAction']);
});