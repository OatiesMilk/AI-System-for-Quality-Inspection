<?php

namespace App\Notifications;

use App\Models\Inspection;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InspectionMarkedForRework extends Notification
{
    use Queueable;

    public function __construct(protected Inspection $inspection)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inspection = $this->inspection;
        $batch = $inspection->batch;

        return (new MailMessage)
            ->subject("Inspection #{$inspection->id} marked for rework")
            ->greeting("Hello {$notifiable->name},")
            ->line("Inspection #{$inspection->id} has been marked for rework by quality inspection.")
            ->line("Batch: {$batch?->batch_code}")
            ->line('Checkpoint: '.str_replace('_', ' ', $inspection->checkpoint))
            ->when($batch?->shift, fn ($mail, $shift) => $mail->line("Shift: {$shift}"))
            ->action('View inspection', $this->publicUrl(route('constructor.inspections.show', $inspection, false)))
            ->line('Please resolve the rework and mark it complete once addressed.');
    }

    protected function publicUrl(string $path): string
    {
        return rtrim(config('app.url'), '/').$path;
    }
}
