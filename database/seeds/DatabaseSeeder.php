<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Seed 2 admins
        factory(App\User::class, 2)->states('admin')->create();

        // Seed 3 organizers
        $organizers = factory(App\User::class, 3)->states('organizer')->create();

        // Seed 10 customers
        factory(App\User::class, 10)->states('customer')->create();

        // Seed 5 events (created by organizers)
        $events = factory(App\Event::class, 5)->create();

        // Seed 15 tickets (3 tickets per event)
        $events->each(function ($event) {
            factory(App\Ticket::class, 3)->create(['event_id' => $event->id]);
        });

        // Seed 20 bookings
        factory(App\Booking::class, 20)->create()->each(function ($booking) {
            // Create payment for confirmed bookings
            if ($booking->status === 'confirmed') {
                $amount = $booking->ticket->price * $booking->quantity;
                factory(App\Payment::class)->create([
                    'booking_id' => $booking->id,
                    'amount' => $amount,
                    'status' => 'success',
                ]);
            }
        });

        $this->command->info('Database seeded successfully!');
        $this->command->info('2 admins, 3 organizers, 10 customers created');
        $this->command->info('5 events, 15 tickets, 20 bookings created');
    }
}
