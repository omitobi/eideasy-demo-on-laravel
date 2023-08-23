<?php

declare(strict_types=1);

namespace App\Services\EidEasy;

use Illuminate\Support\ServiceProvider;
use League\OAuth2\Client\Provider\GenericProvider;
use Transprime\Url\Url;

class EidEasyOauthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->instance(
            EidEasyOauth::class,
            new EidEasyOauth([
                'clientId' => config('identity-auth.eideasy_client_id'),
                'clientSecret' => config('identity-auth.eideasy_secret'),
                'redirectUri' => Url::make(config('app.url'), '/identity/return')->toString(), // The url that will run this code snippet
                'urlAuthorize' => Url::make(
                    fullDomain: config('identity-auth.eideasy_base_url'),
                    path: '/oauth/authorize',
                )->toString(),
                'urlAccessToken' => Url::make(
                    fullDomain: config('identity-auth.eideasy_base_url'),
                    path: '/oauth/access_token',
                )->toString(),
                'urlResourceOwnerDetails' => Url::make(
                    fullDomain: config('identity-auth.eideasy_base_url'),
                    path: '/api/v2/user_data',
                )->toString(),
            ]),
        );
    }
}
