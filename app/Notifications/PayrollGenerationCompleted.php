<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Notification;

class PayrollGenerationCompleted extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $isSuccess = !isset($this->data['error']);

        if ($isSuccess) {
            return (new MailMessage)
                ->subject($this->data['title'])
                ->greeting('Hello!')
                ->line($this->data['message'])
                ->line('Period: ' . ($this->data['period']['period_name'] ?? 'N/A'))
                ->line('Total Processed: ' . ($this->data['results']['total_processed'] ?? 0))
                ->line('Successful: ' . ($this->data['results']['successful'] ?? 0))
                ->line('Failed: ' . ($this->data['results']['failed'] ?? 0))
                ->line('Skipped: ' . ($this->data['results']['skipped'] ?? 0))
                ->action('View Payroll', url('/secret/payrolls'))
                ->line('Thank you for using our application!');
        } else {
            return (new MailMessage)
                ->error()
                ->subject($this->data['title'])
                ->greeting('Hello!')
                ->line($this->data['message'])
                ->line('Error: ' . $this->data['error'])
                ->action('Check System', url('/secret'))
                ->line('Please check the system logs for more details.');
        }
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->data['title'],
            'message' => $this->data['message'],
            'type' => isset($this->data['error']) ? 'error' : 'success',
            'data' => $this->data,
            'action_url' => url('/secret/payrolls'),
            'created_at' => now(),
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return $this->data;
    }
}
