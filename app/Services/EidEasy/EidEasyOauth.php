<?php

declare(strict_types=1);

namespace App\Services\EidEasy;

use Illuminate\Http\Request;
use League\OAuth2\Client\Provider\GenericProvider;

class EidEasyOauth extends GenericProvider
{
    public function validateState(Request $request)
    {
        if (
            !session('_state') &&
            !$request->get('state') &&
            $request->get('state') !== session('_state')
        ) {
            abort(404, 'Invalid request');
        }
    }
}
