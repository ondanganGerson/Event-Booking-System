<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Event;
use App\Ticket;
use App\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test customer can view their bookings.
     *
     * @return void
     */
    public function testCustomerCanViewTheirBookings()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        // Create bookings for this customer
        factory(Booking::class, 3)->create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
        ]);

        $response = $this->actingAs($customer, 'api')
            ->getJson('/api/bookings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'ticket_id',
                        'quantity',
                        'status',
                        'ticket' => [
                            'id',
                            'type',
                            'price',
                            'event' => [
                                'id',
                                'title',
                                'date',
                                'location',
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /**
     * Test customer only sees their own bookings.
     *
     * @return void
     */
    public function testCustomerOnlySeesOwnBookings()
    {
        $customer1 = factory(User::class)->states('customer')->create();
        $customer2 = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        // Create bookings for both customers
        factory(Booking::class, 2)->create([
            'user_id' => $customer1->id,
            'ticket_id' => $ticket->id,
        ]);

        factory(Booking::class, 3)->create([
            'user_id' => $customer2->id,
            'ticket_id' => $ticket->id,
        ]);

        $response = $this->actingAs($customer1, 'api')
            ->getJson('/api/bookings');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test guest cannot view bookings.
     *
     * @return void
     */
    public function testGuestCannotViewBookings()
    {
        $response = $this->getJson('/api/bookings');

        $response->assertStatus(401);
    }

    /**
     * Test customer can create booking for valid ticket.
     *
     * @return void
     */
    public function testCustomerCanCreateBookingForValidTicket()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create([
            'event_id' => $event->id,
            'price' => 50.00,
            'quantity' => 100,
        ]);

        $response = $this->actingAs($customer, 'api')
            ->postJson("/api/tickets/{$ticket->id}/bookings", [
                'quantity' => 2,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => [
                    'booking' => [
                        'user_id' => $customer->id,
                        'ticket_id' => $ticket->id,
                        'quantity' => 2,
                        'status' => 'pending',
                    ],
                    'total_amount' => 100.00,
                ],
            ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);
    }

    /**
     * Test organizer cannot create booking.
     *
     * @return void
     */
    public function testOrganizerCannotCreateBooking()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($organizer, 'api')
            ->postJson("/api/tickets/{$ticket->id}/bookings", [
                'quantity' => 1,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only customers can create bookings',
            ]);
    }

    /**
     * Test admin cannot create booking.
     *
     * @return void
     */
    public function testAdminCannotCreateBooking()
    {
        $admin = factory(User::class)->states('admin')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/tickets/{$ticket->id}/bookings", [
                'quantity' => 1,
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Only customers can create bookings',
            ]);
    }

    /**
     * Test guest cannot create booking.
     *
     * @return void
     */
    public function testGuestCannotCreateBooking()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->postJson("/api/tickets/{$ticket->id}/bookings", [
            'quantity' => 1,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test booking creation requires valid quantity.
     *
     * @return void
     */
    public function testBookingCreationRequiresValidQuantity()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($customer, 'api')
            ->postJson("/api/tickets/{$ticket->id}/bookings", [
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test cannot book non-existent ticket.
     *
     * @return void
     */
    public function testCannotBookNonExistentTicket()
    {
        $customer = factory(User::class)->states('customer')->create();

        $response = $this->actingAs($customer, 'api')
            ->postJson('/api/tickets/999/bookings', [
                'quantity' => 1,
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Ticket not found',
            ]);
    }

    /**
     * Test cannot book tickets for past events.
     *
     * @return void
     */
    public function testCannotBookTicketsForPastEvents()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->subDays(1),
        ]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($customer, 'api')
            ->postJson("/api/tickets/{$ticket->id}/bookings", [
                'quantity' => 1,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot book tickets for past events',
            ]);
    }

    /**
     * Test cannot book more tickets than available.
     *
     * @return void
     */
    public function testCannotBookMoreTicketsThanAvailable()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create([
            'event_id' => $event->id,
            'quantity' => 10,
        ]);

        // Already book 8 tickets
        factory(Booking::class)->create([
            'ticket_id' => $ticket->id,
            'quantity' => 8,
            'status' => 'confirmed',
        ]);

        // Try to book 5 more (only 2 available)
        $response = $this->actingAs($customer, 'api')
            ->postJson("/api/tickets/{$ticket->id}/bookings", [
                'quantity' => 5,
            ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Not enough tickets available',
                'data' => [
                    'requested' => 5,
                    'available' => 2,
                ],
            ]);
    }

    /**
     * Test cancelled bookings don't count towards availability.
     *
     * @return void
     */
    public function testCancelledBookingsDontCountTowardsAvailability()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create([
            'event_id' => $event->id,
            'quantity' => 10,
        ]);

        // Book 5 tickets and cancel them
        factory(Booking::class)->create([
            'ticket_id' => $ticket->id,
            'quantity' => 5,
            'status' => 'cancelled',
        ]);

        // Should still be able to book 10 tickets
        $response = $this->actingAs($customer, 'api')
            ->postJson("/api/tickets/{$ticket->id}/bookings", [
                'quantity' => 10,
            ]);

        $response->assertStatus(201);
    }

    /**
     * Test customer can cancel own pending booking.
     *
     * @return void
     */
    public function testCustomerCanCancelOwnPendingBooking()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);
        $booking = factory(Booking::class)->create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($customer, 'api')
            ->putJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'data' => [
                    'id' => $booking->id,
                    'status' => 'cancelled',
                ],
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Test admin can cancel any booking.
     *
     * @return void
     */
    public function testAdminCanCancelAnyBooking()
    {
        $customer = factory(User::class)->states('customer')->create();
        $admin = factory(User::class)->states('admin')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);
        $booking = factory(Booking::class)->create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'api')
            ->putJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Booking cancelled successfully',
            ]);
    }

    /**
     * Test customer cannot cancel other customer's booking.
     *
     * @return void
     */
    public function testCustomerCannotCancelOtherCustomerBooking()
    {
        $customer1 = factory(User::class)->states('customer')->create();
        $customer2 = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);
        $booking = factory(Booking::class)->create([
            'user_id' => $customer1->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($customer2, 'api')
            ->putJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized to cancel this booking',
            ]);
    }

    /**
     * Test cannot cancel non-existent booking.
     *
     * @return void
     */
    public function testCannotCancelNonExistentBooking()
    {
        $customer = factory(User::class)->states('customer')->create();

        $response = $this->actingAs($customer, 'api')
            ->putJson('/api/bookings/999/cancel');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Booking not found',
            ]);
    }

    /**
     * Test cannot cancel already cancelled booking.
     *
     * @return void
     */
    public function testCannotCancelAlreadyCancelledBooking()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);
        $booking = factory(Booking::class)->create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($customer, 'api')
            ->putJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Booking is already cancelled',
            ]);
    }

    /**
     * Test cannot cancel confirmed booking.
     *
     * @return void
     */
    public function testCannotCancelConfirmedBooking()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);
        $booking = factory(Booking::class)->create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($customer, 'api')
            ->putJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot cancel confirmed booking. Please contact support for refund.',
            ]);
    }

    /**
     * Test cannot cancel booking for past event.
     *
     * @return void
     */
    public function testCannotCancelBookingForPastEvent()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->subDays(1),
        ]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);
        $booking = factory(Booking::class)->create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($customer, 'api')
            ->putJson("/api/bookings/{$booking->id}/cancel");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot cancel booking for past events',
            ]);
    }

    /**
     * Test booking creation calculates total amount correctly.
     *
     * @return void
     */
    public function testBookingCreationCalculatesTotalAmountCorrectly()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'date' => now()->addWeeks(2),
        ]);
        $ticket = factory(Ticket::class)->create([
            'event_id' => $event->id,
            'price' => 25.50,
            'quantity' => 100,
        ]);

        $response = $this->actingAs($customer, 'api')
            ->postJson("/api/tickets/{$ticket->id}/bookings", [
                'quantity' => 3,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'total_amount' => 76.50,
                ],
            ]);
    }

    /**
     * Test bookings are ordered by created date descending.
     *
     * @return void
     */
    public function testBookingsAreOrderedByCreatedDateDescending()
    {
        $customer = factory(User::class)->states('customer')->create();
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        // Create bookings at different times
        $booking1 = factory(Booking::class)->create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'created_at' => now()->subHours(3),
        ]);

        $booking2 = factory(Booking::class)->create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'created_at' => now()->subHours(1),
        ]);

        $booking3 = factory(Booking::class)->create([
            'user_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'created_at' => now()->subHours(2),
        ]);

        $response = $this->actingAs($customer, 'api')
            ->getJson('/api/bookings');

        $response->assertStatus(200);

        $bookings = $response->json('data');
        $this->assertEquals($booking2->id, $bookings[0]['id']);
        $this->assertEquals($booking3->id, $bookings[1]['id']);
        $this->assertEquals($booking1->id, $bookings[2]['id']);
    }
}
