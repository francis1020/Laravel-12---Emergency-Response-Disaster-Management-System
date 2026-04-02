<?php

namespace App\Notifications;

use App\Models\EmergencyReport;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ResponderStatusChanged extends Notification
{
    use Queueable;

    public $report;
    public $responder;
    public $action; // 'joined', 'dispatched', 'arrived', 'resolved', 'cancelled'
    public $role; // 'primary', 'secondary'

    /**
     * Create a new notification instance.
     */
    public function __construct(EmergencyReport $report, User $responder, string $action, string $role)
    {
        $this->report = $report;
        $this->responder = $responder;
        $this->action = $action;
        $this->role = $role;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $actionMessages = [
            'assigned' => 'has been assigned as',
            'joined' => 'has joined as',
            'dispatched' => 'has been dispatched',
            'arrived' => 'has arrived on scene',
            'resolved' => 'has resolved',
            'cancelled' => 'has cancelled their response',
        ];

        $roleText = $this->role === 'primary' ? 'Primary Responder' : ($this->role === 'secondary' ? 'Secondary Responder' : 'Support Responder');
        $actionText = $actionMessages[$this->action] ?? 'has updated status';
        
        // For 'assigned' action, use different message format
        if ($this->action === 'assigned') {
            $message = "You have been assigned as {$roleText} to Report #{$this->report->id}.";
        } else {
            $message = "{$this->responder->name} ({$roleText}) {$actionText} Report #{$this->report->id}.";
        }

        // Determine notification type
        $type = 'info';
        if ($this->action === 'resolved') {
            $type = 'success';
        } elseif ($this->action === 'cancelled') {
            $type = 'warning';
        } elseif ($this->action === 'joined' || $this->action === 'assigned') {
            $type = 'info';
        }

        return [
            'report_id' => $this->report->id,
            'responder_id' => $this->responder->id,
            'responder_name' => $this->responder->name,
            'action' => $this->action,
            'role' => $this->role,
            'message' => $message,
            'type' => $type,
        ];
    }
}

