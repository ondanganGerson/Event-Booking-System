<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Booking;

class BookingConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The booking instance.
     *
     * @var Booking
     */
    protected $booking;

    /**
     * Create a new notification instance.
     *
     * @param  Booking  $booking
     * @return void
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
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
        $event = $this->booking->ticket->event;
        $ticket = $this->booking->ticket;
        $payment = $this->booking->payment;

        return (new MailMessage)
                    ->subject('Booking Confirmed - ' . $event->title)
                    ->greeting('Hello ' . $notifiable->name . '!')
                    ->line('Your booking has been confirmed successfully.')
                    ->line('**Event Details:**')
                    ->line('Event: ' . $event->title)
                    ->line('Date: ' . $event->date->format('F d, Y'))
                    ->line('Location: ' . $event->location)
                    ->line('')
                    ->line('**Booking Details:**')
                    ->line('Ticket Type: ' . $ticket->type)
                    ->line('Quantity: ' . $this->booking->quantity)
                    ->line('Price per Ticket: $' . number_format($ticket->price, 2))
                    ->line('Total Amount: $' . number_format($payment->amount, 2))
                    ->line('Booking Reference: #' . $this->booking->id)
                    ->line('')
                    ->line('Please keep this email for your records. You may need to present your booking reference at the event.')
                    ->action('View Booking Details', url('/bookings/' . $this->booking->id))
                    ->line('Thank you for your booking!')
                    ->line('')
                    ->line('If you have any questions, please contact our support team.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'booking_id' => $this->booking->id,
            'event_name' => $this->booking->ticket->event->title,
            'quantity' => $this->booking->quantity,
            'total_amount' => $this->booking->payment->amount,
            'status' => $this->booking->status,
        ];
    }
}
