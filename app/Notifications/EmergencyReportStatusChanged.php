<?php

namespace App\Notifications;

use App\Models\EmergencyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Notifications\Messages\MailMessage; // Temporarily disabled - uncomment when enabling email
use Illuminate\Notifications\Notification;

class EmergencyReportStatusChanged extends Notification
{
    use Queueable;

    public $report;
    public $oldStatus;
    public $newStatus;

    /**
     * Create a new notification instance.
     */
    public function __construct(EmergencyReport $report, $oldStatus, $newStatus)
    {
        $this->report = $report;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Temporarily disabled email notifications - only using database
        return ['database'];
        // To enable email notifications, add 'mail' to the array: return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     * Temporarily disabled - uncomment to enable email notifications
     */
    /*
    public function toMail(object $notifiable): MailMessage
    {
        $statusMessages = [
            'acknowledged' => 'has been acknowledged',
            'dispatched' => 'responder has been dispatched',
            'in_progress' => 'responder is now on scene',
            'resolved' => 'has been resolved',
            'cancelled' => 'has been cancelled',
        ];

        $message = $statusMessages[$this->newStatus] ?? 'status has been updated';

        return (new MailMessage)
                    ->subject('Emergency Report Status Update')
                    ->line("Report #{$this->report->id} {$message}.")
                    ->action('View Report', route('reports.show', $this->report->id))
                    ->line('Thank you for using our emergency response system!');
    }
    */

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $statusMessages = [
            'new' => 'has been created',
            'updated' => 'has been updated',
            'acknowledged' => 'has been acknowledged',
            'dispatched' => 'responder has been dispatched',
            'in_progress' => 'responder is now on scene',
            'resolved' => 'has been resolved',
            'cancelled' => 'has been cancelled',
        ];

        $message = $statusMessages[$this->newStatus] ?? 'status has been updated';

        return [
            'report_id' => $this->report->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'message' => "Report #{$this->report->id} {$message}.",
            'type' => $this->report->type,
            'severity' => $this->report->severity_level,
        ];
    }
}

