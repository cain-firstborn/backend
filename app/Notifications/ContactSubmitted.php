<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ContactSubmitted extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Message $message)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject(trans('notification.contact.subject', ['name' => $this->message->name]))
            ->greeting(trans('notification.contact.greeting'))
            ->line($this->table())
            ->markdown('notifications::email', [
                'regards' => false,
                'footer'  => false,
            ]);
    }

    /**
     * Returns a formatted HTML table of the contact form data.
     *
     * @return HtmlString
     */
    protected function table(): HtmlString
    {
        return new HtmlString(trans('notification.contact.table', [
            'name'    => $this->message->name,
            'email'   => $this->message->user->email,
            'message' => $this->message->message,
        ]));
    }
}
