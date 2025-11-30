<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Event;
use App\Ticket;
use App\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test organizer can create ticket for own event.
     *
     * @return void
     */
    public function testOrganizerCanCreateTicketForOwnEvent()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $ticketData = [
            'type' => 'VIP',
            'price' => 99.99,
            'quantity' => 100,
        ];

        $response = $this->actingAs($organizer, 'api')
            ->postJson("/api/events/{$event->id}/tickets", $ticketData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Ticket created successfully',
                'data' => [
                    'type' => 'VIP',
                    'price' => '99.99',
                    'quantity' => 100,
                    'event_id' => $event->id,
                ],
            ]);

        $this->assertDatabaseHas('tickets', [
            'type' => 'VIP',
            'event_id' => $event->id,
        ]);
    }

    /**
     * Test admin can create ticket for any event.
     *
     * @return void
     */
    public function testAdminCanCreateTicketForAnyEvent()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $admin = factory(User::class)->states('admin')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $ticketData = [
            'type' => 'Standard',
            'price' => 49.99,
            'quantity' => 200,
        ];

        $response = $this->actingAs($admin, 'api')
            ->postJson("/api/events/{$event->id}/tickets", $ticketData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Ticket created successfully',
            ]);
    }

    /**
     * Test organizer cannot create ticket for other organizer's event.
     *
     * @return void
     */
    public function testOrganizerCannotCreateTicketForOtherOrganizerEvent()
    {
        $organizer1 = factory(User::class)->states('organizer')->create();
        $organizer2 = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer1->id]);

        $ticketData = [
            'type' => 'Unauthorized',
            'price' => 50.00,
            'quantity' => 100,
        ];

        $response = $this->actingAs($organizer2, 'api')
            ->postJson("/api/events/{$event->id}/tickets", $ticketData);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized to create tickets for this event',
            ]);
    }

    /**
     * Test customer cannot create ticket.
     *
     * @return void
     */
    public function testCustomerCannotCreateTicket()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $customer = factory(User::class)->states('customer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $ticketData = [
            'type' => 'Customer Ticket',
            'price' => 30.00,
            'quantity' => 50,
        ];

        $response = $this->actingAs($customer, 'api')
            ->postJson("/api/events/{$event->id}/tickets", $ticketData);

        $response->assertStatus(403);
    }

    /**
     * Test guest cannot create ticket.
     *
     * @return void
     */
    public function testGuestCannotCreateTicket()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $ticketData = [
            'type' => 'Guest Ticket',
            'price' => 25.00,
            'quantity' => 30,
        ];

        $response = $this->postJson("/api/events/{$event->id}/tickets", $ticketData);

        $response->assertStatus(401);
    }

    /**
     * Test ticket creation requires valid data.
     *
     * @return void
     */
    public function testTicketCreationRequiresValidData()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($organizer, 'api')
            ->postJson("/api/events/{$event->id}/tickets", [
                'type' => '',
                'price' => -10,
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'price', 'quantity']);
    }

    /**
     * Test ticket price cannot be negative.
     *
     * @return void
     */
    public function testTicketPriceCannotBeNegative()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($organizer, 'api')
            ->postJson("/api/events/{$event->id}/tickets", [
                'type' => 'VIP',
                'price' => -50.00,
                'quantity' => 100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price']);
    }

    /**
     * Test ticket quantity must be at least 1.
     *
     * @return void
     */
    public function testTicketQuantityMustBeAtLeastOne()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($organizer, 'api')
            ->postJson("/api/events/{$event->id}/tickets", [
                'type' => 'Standard',
                'price' => 25.00,
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    /**
     * Test creating ticket for non-existent event returns 404.
     *
     * @return void
     */
    public function testCreatingTicketForNonExistentEventReturns404()
    {
        $organizer = factory(User::class)->states('organizer')->create();

        $ticketData = [
            'type' => 'VIP',
            'price' => 99.99,
            'quantity' => 100,
        ];

        $response = $this->actingAs($organizer, 'api')
            ->postJson('/api/events/999/tickets', $ticketData);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Event not found',
            ]);
    }

    /**
     * Test organizer can update ticket for own event.
     *
     * @return void
     */
    public function testOrganizerCanUpdateTicketForOwnEvent()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create([
            'event_id' => $event->id,
            'type' => 'VIP',
            'price' => 99.99,
        ]);

        $response = $this->actingAs($organizer, 'api')
            ->putJson("/api/tickets/{$ticket->id}", [
                'type' => 'Super VIP',
                'price' => 149.99,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ticket updated successfully',
                'data' => [
                    'id' => $ticket->id,
                    'type' => 'Super VIP',
                    'price' => '149.99',
                ],
            ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'type' => 'Super VIP',
            'price' => 149.99,
        ]);
    }

    /**
     * Test admin can update any ticket.
     *
     * @return void
     */
    public function testAdminCanUpdateAnyTicket()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $admin = factory(User::class)->states('admin')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($admin, 'api')
            ->putJson("/api/tickets/{$ticket->id}", [
                'type' => 'Admin Updated',
                'price' => 75.00,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ticket updated successfully',
            ]);
    }

    /**
     * Test organizer cannot update ticket for other organizer's event.
     *
     * @return void
     */
    public function testOrganizerCannotUpdateTicketForOtherOrganizerEvent()
    {
        $organizer1 = factory(User::class)->states('organizer')->create();
        $organizer2 = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer1->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($organizer2, 'api')
            ->putJson("/api/tickets/{$ticket->id}", [
                'type' => 'Unauthorized Update',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized to update this ticket',
            ]);
    }

    /**
     * Test customer cannot update ticket.
     *
     * @return void
     */
    public function testCustomerCannotUpdateTicket()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $customer = factory(User::class)->states('customer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($customer, 'api')
            ->putJson("/api/tickets/{$ticket->id}", [
                'type' => 'Customer Update',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test updating non-existent ticket returns 404.
     *
     * @return void
     */
    public function testUpdatingNonExistentTicketReturns404()
    {
        $organizer = factory(User::class)->states('organizer')->create();

        $response = $this->actingAs($organizer, 'api')
            ->putJson('/api/tickets/999', [
                'type' => 'Updated Ticket',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Ticket not found',
            ]);
    }

    /**
     * Test organizer can delete ticket without bookings.
     *
     * @return void
     */
    public function testOrganizerCanDeleteTicketWithoutBookings()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($organizer, 'api')
            ->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ticket deleted successfully',
            ]);

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
        ]);
    }

    /**
     * Test admin can delete any ticket without bookings.
     *
     * @return void
     */
    public function testAdminCanDeleteAnyTicketWithoutBookings()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $admin = factory(User::class)->states('admin')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($admin, 'api')
            ->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Ticket deleted successfully',
            ]);
    }

    /**
     * Test cannot delete ticket with existing bookings.
     *
     * @return void
     */
    public function testCannotDeleteTicketWithExistingBookings()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $customer = factory(User::class)->states('customer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        // Create a booking for this ticket
        factory(Booking::class)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
        ]);

        $response = $this->actingAs($organizer, 'api')
            ->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cannot delete ticket with existing bookings',
            ]);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
        ]);
    }

    /**
     * Test organizer cannot delete ticket for other organizer's event.
     *
     * @return void
     */
    public function testOrganizerCannotDeleteTicketForOtherOrganizerEvent()
    {
        $organizer1 = factory(User::class)->states('organizer')->create();
        $organizer2 = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer1->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($organizer2, 'api')
            ->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized to delete this ticket',
            ]);
    }

    /**
     * Test customer cannot delete ticket.
     *
     * @return void
     */
    public function testCustomerCannotDeleteTicket()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $customer = factory(User::class)->states('customer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($customer, 'api')
            ->deleteJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(403);
    }

    /**
     * Test deleting non-existent ticket returns 404.
     *
     * @return void
     */
    public function testDeletingNonExistentTicketReturns404()
    {
        $organizer = factory(User::class)->states('organizer')->create();

        $response = $this->actingAs($organizer, 'api')
            ->deleteJson('/api/tickets/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Ticket not found',
            ]);
    }

    /**
     * Test ticket creation with free price (0.00).
     *
     * @return void
     */
    public function testTicketCreationWithFreePrice()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $ticketData = [
            'type' => 'Free Entry',
            'price' => 0.00,
            'quantity' => 100,
        ];

        $response = $this->actingAs($organizer, 'api')
            ->postJson("/api/events/{$event->id}/tickets", $ticketData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'price' => '0.00',
                ],
            ]);
    }

    /**
     * Test ticket update validation.
     *
     * @return void
     */
    public function testTicketUpdateValidation()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);
        $ticket = factory(Ticket::class)->create(['event_id' => $event->id]);

        $response = $this->actingAs($organizer, 'api')
            ->putJson("/api/tickets/{$ticket->id}", [
                'price' => -50,
                'quantity' => -10,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['price', 'quantity']);
    }
}
