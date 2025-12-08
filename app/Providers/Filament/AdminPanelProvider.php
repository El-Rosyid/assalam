<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use App\Http\Responses\CustomLogoutResponse;
use App\Http\Responses\CustomLoginResponse;
use Filament\Notifications\Notification;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Custom logout response - redirect to custom login page
        $this->app->bind(LogoutResponse::class, CustomLogoutResponse::class);
        
        // Custom login response - redirect to admin dashboard
        $this->app->bind(LoginResponse::class, CustomLoginResponse::class);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->brandName('TK ABA ASSALAM')
            ->default()
            ->sidebarCollapsibleOnDesktop(true)
            ->id('admin')
            ->path('admin')
            ->authGuard('web')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s') // Changed from 10s to reduce server load
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([                
                \App\Filament\Widgets\SchoolStatsOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                \App\Http\Middleware\FilamentAuthenticate::class,
            ]);
    }
}
