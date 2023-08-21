<?php

use App\Actions\Fortify\CreateNewUser;
use App\Models\User;
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

Route::get('identity/login', function () {
    $baseUrl = config('identity-auth.eideasy_base_url');
    $clientId = config('identity-auth.eideasy_client_id');

    $url = Url::make(
        fullDomain: $baseUrl,
        path: '/oauth/authorize',
    )
        ->addToQuery('client_id', $clientId)
        ->addToQuery('redirect_uri', \route('identity.return'))
        ->addToQuery('response_type', 'code');

    // Generate a URL
    return redirect()->away($url->toString());
})
    ->name('identity.login')
    ->middleware(array_filter([
        'guest:'.config('fortify.guard'),
        $limiter ? 'throttle:'.$limiter : null,
    ]));

Route::get('identity/return', function (Request $request) {
    $baseUrl = config('identity-auth.eideasy_base_url');
    $clientId = config('identity-auth.eideasy_client_id');
    $secret = config('identity-auth.eideasy_secret');

    $code = $request->get('code');

    // Get access token

    $response = Http::asForm()
        ->post(new Url(fullDomain: $baseUrl, path: '/oauth/access_token'), [
            'client_id' => $clientId,
            'client_secret' => $secret,
            'redirect_uri' => \route('identity.return'),
            'code' => $code,
            'grant_type' => 'authorization_code',
        ]
    );

    $accessToken  = $response->json('access_token');

    // Get user details with access token.
    $dataResponse = Http::withToken($accessToken)
        ->get(new Url(fullDomain: $baseUrl, path: '/api/v2/user_data'))
        ->json();

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
