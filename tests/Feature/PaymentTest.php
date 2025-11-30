<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use App\Event;
use App\Ticket;
use App\Booking;
use App\Payment;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test payment can be created for a booking.
     *
     * @return void
     */
    public function testPaymentCanBeCreatedForBooking()
    {
        $customer = factory(User::class)->states('customer')->create(['api_token' => 'test-token']);
        $event = factory(Event::class)->create();
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id, 'price' => 100]);
        $booking = factory(Booking::class)->create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);

        $response = $this->json('POST', "/api/bookings/{$booking->id}/payment", [], [
            'Authorization' => 'Bearer test-token',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['payment', 'booking'],
            ]);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
        ]);
    }

    /**
     * Test payment cannot be created for non-existent booking.
     *
     * @return void
     */
    public function testPaymentCannotBeCreatedForNonExistentBooking()
    {
        $customer = factory(User::class)->states('customer')->create(['api_token' => 'test-token']);

        $response = $this->json('POST', '/api/bookings/999/payment', [], [
            'Authorization' => 'Bearer test-token',
        ]);

        $response->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    /**
     * Test payment details can be retrieved.
     *
     * @return void
     */
    public function testPaymentDetailsCanBeRetrieved()
    {
        $customer = factory(User::class)->states('customer')->create(['api_token' => 'test-token']);
        $booking = factory(Booking::class)->create(['user_id' => $customer->id]);
        $payment = factory(Payment::class)->create([
            'booking_id' => $booking->id,
            'amount' => 200,
            'status' => 'success',
        ]);

        $response = $this->json('GET', "/api/payments/{$payment->id}", [], [
            'Authorization' => 'Bearer test-token',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $payment->id,
                    'amount' => '200.00',
                ],
            ]);
    }

    /**
     * Test duplicate payment cannot be created.
     *
     * @return void
     */
    public function testDuplicatePaymentCannotBeCreated()
    {
        $customer = factory(User::class)->states('customer')->create(['api_token' => 'test-token']);
        $booking = factory(Booking::class)->create([
            'user_id' => $customer->id,
            'status' => 'pending',
        ]);
        factory(Payment::class)->create(['booking_id' => $booking->id]);

        $response = $this->json('POST', "/api/bookings/{$booking->id}/payment", [], [
            'Authorization' => 'Bearer test-token',
        ]);

        $response->assertStatus(400)
            ->assertJson(['success' => false]);
    }
}
