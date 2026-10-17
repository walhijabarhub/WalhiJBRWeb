<?php

namespace App\Notifications;

use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmNewsletterSubscription extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Subscriber $subscriber) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Konfirmasi langganan Nawala WALHI Jawa Barat')
            ->greeting('Halo!')
            ->line('Terima kasih telah berlangganan Nawala WALHI Jawa Barat. Klik tombol di bawah untuk mengonfirmasi alamat email Anda.')
            ->action('Konfirmasi Langganan', $this->subscriber->confirmationUrl())
            ->line('Tautan ini berlaku 7 hari. Jika Anda tidak merasa mendaftar, abaikan email ini — Anda tidak akan menerima email lain dari kami.')
            ->line('Berhenti berlangganan kapan saja: '.$this->subscriber->unsubscribeUrl());
    }
}
