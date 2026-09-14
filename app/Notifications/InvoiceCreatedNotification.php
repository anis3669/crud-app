<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invoice $invoice
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("New Invoice #{$this->invoice->invoice_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line('A new invoice has been created.')
            ->line("Invoice: {$this->invoice->invoice_number}")
            ->line("Customer: {$this->invoice->customer_name}")
            ->line("Total: {$this->invoice->total}")
            ->action('View Invoice', url("/invoices/{$this->invoice->id}"))
            ->line('Thank you.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'customer_name' => $this->invoice->customer_name,
            'total' => $this->invoice->total,
            'message' => "New invoice #{$this->invoice->invoice_number} has been created.",
        ];
    }
}
