<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotifyUser extends Notification implements ShouldQueue
{
    use Queueable;

    public $maildata;

    /**
     * Create a new notification instance.
     * 
     * @param  mixed  $mail_data
     * @return void
     */
    public function __construct($maildata)
    {
        $this->maildata = $maildata;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->from($this->maildata['from'])
            ->subject($this->maildata['subject'])
            ->markdown('mail.' . $this->maildata['markdown'], ['maildata' => $this->maildata['maildata']]);

        if (isset($this->maildata['attachment'])) {
            $message->attach($this->maildata['attachment']);
        }

        return $message;
    }
}
