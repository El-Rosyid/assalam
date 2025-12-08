<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate as BaseAuthenticate;

class FilamentAuthenticate extends BaseAuthenticate
{
    /**
     * Redirect to custom login page when unauthenticated
     * This ensures all authentication redirects go to / instead of /login
     */
    protected function redirectTo($request): ?string
    {
        return '/';
    }
}
