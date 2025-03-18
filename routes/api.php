<?php

use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



Route::post('/getToken', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['The provided credentials are incorrect.'],
        ]);
    }

    // Buat token dengan ability "access"
    $token = $user->createToken('Personal Access Token', ['access'])->plainTextToken;

    // Update token untuk memiliki expired time 30 menit
    DB::table('personal_access_tokens')
        ->where('tokenable_id', $user->id)
        ->where('token', hash('sha256', explode('|', $token)[1])) // Hash token yang disimpan
        ->update(['expires_at' => Carbon::now()->addMinutes(30)]);

    return response()->json([
        'token' => $token,
        'expires_at' => Carbon::now()->addMinutes(30)->toDateTimeString(),
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('products', ProductController::class);
});
