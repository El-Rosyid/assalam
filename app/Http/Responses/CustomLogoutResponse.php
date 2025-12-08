<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;

class CustomLogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // Invalidate session and regenerate token for security
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        // Redirect to custom login page after logout
        return redirect('/');
    }
}