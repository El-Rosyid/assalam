<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;

class BaseAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $body;
    protected $icon;
    protected $iconColor;
    protected $actions;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $body, $icon = 'heroicon-o-bell', $iconColor = 'primary', $actions = [])
    {
        $this->title = $title;
        $this->body = $body;
        $this->icon = $icon;
        $this->iconColor = $iconColor;
        $this->actions = $actions;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification for Filament.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'icon' => $this->icon,
            'iconColor' => $this->iconColor,
            'actions' => $this->actions,
            'format' => 'filament',
            'duration' => 'persistent', // Tidak hilang otomatis
        ];
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Get Filament notification representation
     * This ensures notifications appear in bell icon and stay until dismissed
     */
    public function toFilament(): FilamentNotification
    {
        $notification = FilamentNotification::make()
            ->title($this->title)
            ->body($this->body)
            ->icon($this->icon)
            ->iconColor($this->iconColor)
            ->persistent(); // PENTING: Notifikasi tidak hilang otomatis, harus klik X

        // Add actions if provided
        if (!empty($this->actions)) {
            $filamentActions = [];
            foreach ($this->actions as $action) {
                $filamentActions[] = Action::make($action['name'] ?? 'view')
                    ->button()
                    ->url($action['url'] ?? '#')
                    ->color($action['color'] ?? 'primary');
            }
            $notification->actions($filamentActions);
        }

        return $notification;
    }

    /**
     * Send notification to all admin users
     */
    public static function sendToAdmins($title, $body, $icon = 'heroicon-o-bell', $iconColor = 'primary', $actions = [])
    {
        $adminUsers = \App\Models\User::whereHas('roles', function($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->get();

        foreach ($adminUsers as $admin) {
            $admin->notify(new static($title, $body, $icon, $iconColor, $actions));
        }
    }
}