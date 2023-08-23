<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
use App\Services\EidEasy\EidEasyOauth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\RegisterResponse;
use Transprime\Url\Url;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

$limiter = config('fortify.limiters.login');

Route::get('identity/login', function (EidEasyOauth $easyOauth) {
    session(['_state' => $easyOauth->getState()]);

    $authorizationUrl = $easyOauth->getAuthorizationUrl([
        // Optional params
        'lang' => 'en',
    ]);

    // Generate a URL
    return redirect()->away($authorizationUrl);
})
    ->name('identity.login')
    ->middleware(array_filter([
        'guest:'.config('fortify.guard'),
        $limiter ? 'throttle:'.$limiter : null,
    ]));

Route::get('identity/return', function (Request $request, EidEasyOauth $easyOauth) {

    $easyOauth->validateState($request);

    // Try to get an access token using the authorization code grant.
    $accessToken = $easyOauth->getAccessToken('authorization_code', [
        'code' => $_GET['code'],
    ]);

    // Using the access token, we may look up details about the
    // resource owner.
    $resourceOwner = $easyOauth->getResourceOwner($accessToken);

    $dataResponse = $resourceOwner->toArray();

    $identifier = hash('sha256', $dataResponse['idcode']);

    if (!$user = User::where('identifier', $identifier)->first()) {
        $createNewUser = new CreateNewUser();
        $user = $createNewUser->createUser([
            'email' => Str::random(16) . '@example.com',
            'password' => Str::random(54),
            'name' => Arr::get($dataResponse, 'firstname') . ' ' . Arr::get($dataResponse, 'lastname'),
            'identifier' => $identifier,
        ]);

        event(new Registered($user));
    }

    Auth::guard()->login($user);

    return app(RegisterResponse::class);
})->name('identity.return');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
