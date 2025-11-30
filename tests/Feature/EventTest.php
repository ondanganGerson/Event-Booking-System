<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\User;
use App\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class EventTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test guest can view all events.
     *
     * @return void
     */
    public function testGuestCanViewAllEvents()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        factory(Event::class, 3)->create(['created_by' => $organizer->id]);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'description',
                            'date',
                            'location',
                            'created_by',
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * Test guest can view single event.
     *
     * @return void
     */
    public function testGuestCanViewSingleEvent()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create([
            'created_by' => $organizer->id,
            'title' => 'Tech Conference 2024',
        ]);

        $response = $this->getJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $event->id,
                    'title' => 'Tech Conference 2024',
                ],
            ]);
    }

    /**
     * Test viewing non-existent event returns 404.
     *
     * @return void
     */
    public function testViewingNonExistentEventReturns404()
    {
        $response = $this->getJson('/api/events/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Event not found',
            ]);
    }

    /**
     * Test organizer can create event.
     *
     * @return void
     */
    public function testOrganizerCanCreateEvent()
    {
        $organizer = factory(User::class)->states('organizer')->create();

        $eventData = [
            'title' => 'New Tech Conference',
            'description' => 'A conference about technology',
            'date' => now()->addWeeks(2)->format('Y-m-d H:i:s'),
            'location' => 'New York, NY',
        ];

        $response = $this->actingAs($organizer, 'api')
            ->postJson('/api/events', $eventData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => [
                    'title' => 'New Tech Conference',
                    'description' => 'A conference about technology',
                    'location' => 'New York, NY',
                    'created_by' => $organizer->id,
                ],
            ]);

        $this->assertDatabaseHas('events', [
            'title' => 'New Tech Conference',
            'created_by' => $organizer->id,
        ]);
    }

    /**
     * Test admin can create event.
     *
     * @return void
     */
    public function testAdminCanCreateEvent()
    {
        $admin = factory(User::class)->states('admin')->create();

        $eventData = [
            'title' => 'Admin Event',
            'description' => 'Event created by admin',
            'date' => now()->addWeeks(2)->format('Y-m-d H:i:s'),
            'location' => 'Los Angeles, CA',
        ];

        $response = $this->actingAs($admin, 'api')
            ->postJson('/api/events', $eventData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Event created successfully',
            ]);
    }

    /**
     * Test customer cannot create event.
     *
     * @return void
     */
    public function testCustomerCannotCreateEvent()
    {
        $customer = factory(User::class)->states('customer')->create();

        $eventData = [
            'title' => 'Customer Event',
            'description' => 'This should not be created',
            'date' => now()->addWeeks(2)->format('Y-m-d H:i:s'),
            'location' => 'Chicago, IL',
        ];

        $response = $this->actingAs($customer, 'api')
            ->postJson('/api/events', $eventData);

        $response->assertStatus(403);
    }

    /**
     * Test guest cannot create event.
     *
     * @return void
     */
    public function testGuestCannotCreateEvent()
    {
        $eventData = [
            'title' => 'Guest Event',
            'description' => 'This should not be created',
            'date' => now()->addWeeks(2)->format('Y-m-d H:i:s'),
            'location' => 'Boston, MA',
        ];

        $response = $this->postJson('/api/events', $eventData);

        $response->assertStatus(401);
    }

    /**
     * Test event creation requires valid data.
     *
     * @return void
     */
    public function testEventCreationRequiresValidData()
    {
        $organizer = factory(User::class)->states('organizer')->create();

        $response = $this->actingAs($organizer, 'api')
            ->postJson('/api/events', [
                'title' => '',
                'description' => '',
                'date' => 'invalid-date',
                'location' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'date', 'location']);
    }

    /**
     * Test event date must be in the future.
     *
     * @return void
     */
    public function testEventDateMustBeInFuture()
    {
        $organizer = factory(User::class)->states('organizer')->create();

        $response = $this->actingAs($organizer, 'api')
            ->postJson('/api/events', [
                'title' => 'Past Event',
                'description' => 'This should fail',
                'date' => now()->subDays(1)->format('Y-m-d H:i:s'),
                'location' => 'Seattle, WA',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    /**
     * Test organizer can update own event.
     *
     * @return void
     */
    public function testOrganizerCanUpdateOwnEvent()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($organizer, 'api')
            ->putJson("/api/events/{$event->id}", [
                'title' => 'Updated Event Title',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => [
                    'id' => $event->id,
                    'title' => 'Updated Event Title',
                    'description' => 'Updated description',
                ],
            ]);

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'title' => 'Updated Event Title',
        ]);
    }

    /**
     * Test admin can update any event.
     *
     * @return void
     */
    public function testAdminCanUpdateAnyEvent()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $admin = factory(User::class)->states('admin')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($admin, 'api')
            ->putJson("/api/events/{$event->id}", [
                'title' => 'Admin Updated Title',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event updated successfully',
            ]);
    }

    /**
     * Test organizer cannot update other organizer's event.
     *
     * @return void
     */
    public function testOrganizerCannotUpdateOtherOrganizerEvent()
    {
        $organizer1 = factory(User::class)->states('organizer')->create();
        $organizer2 = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer1->id]);

        $response = $this->actingAs($organizer2, 'api')
            ->putJson("/api/events/{$event->id}", [
                'title' => 'Unauthorized Update',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized to update this event',
            ]);
    }

    /**
     * Test customer cannot update event.
     *
     * @return void
     */
    public function testCustomerCannotUpdateEvent()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $customer = factory(User::class)->states('customer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($customer, 'api')
            ->putJson("/api/events/{$event->id}", [
                'title' => 'Customer Update',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test updating non-existent event returns 404.
     *
     * @return void
     */
    public function testUpdatingNonExistentEventReturns404()
    {
        $organizer = factory(User::class)->states('organizer')->create();

        $response = $this->actingAs($organizer, 'api')
            ->putJson('/api/events/999', [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Event not found',
            ]);
    }

    /**
     * Test organizer can delete own event.
     *
     * @return void
     */
    public function testOrganizerCanDeleteOwnEvent()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($organizer, 'api')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event deleted successfully',
            ]);

        $this->assertDatabaseMissing('events', [
            'id' => $event->id,
        ]);
    }

    /**
     * Test admin can delete any event.
     *
     * @return void
     */
    public function testAdminCanDeleteAnyEvent()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $admin = factory(User::class)->states('admin')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($admin, 'api')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Event deleted successfully',
            ]);
    }

    /**
     * Test organizer cannot delete other organizer's event.
     *
     * @return void
     */
    public function testOrganizerCannotDeleteOtherOrganizerEvent()
    {
        $organizer1 = factory(User::class)->states('organizer')->create();
        $organizer2 = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer1->id]);

        $response = $this->actingAs($organizer2, 'api')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized to delete this event',
            ]);
    }

    /**
     * Test customer cannot delete event.
     *
     * @return void
     */
    public function testCustomerCannotDeleteEvent()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $customer = factory(User::class)->states('customer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($customer, 'api')
            ->deleteJson("/api/events/{$event->id}");

        $response->assertStatus(403);
    }

    /**
     * Test deleting non-existent event returns 404.
     *
     * @return void
     */
    public function testDeletingNonExistentEventReturns404()
    {
        $organizer = factory(User::class)->states('organizer')->create();

        $response = $this->actingAs($organizer, 'api')
            ->deleteJson('/api/events/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Event not found',
            ]);
    }

    /**
     * Test event listing includes creator and tickets.
     *
     * @return void
     */
    public function testEventListingIncludesCreatorAndTickets()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $response = $this->getJson('/api/events');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'creator' => [
                                'id',
                                'name',
                                'email',
                            ],
                            'tickets',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * Test cache is cleared after creating event.
     *
     * @return void
     */
    public function testCacheIsClearedAfterCreatingEvent()
    {
        Cache::shouldReceive('flush')->once();

        $organizer = factory(User::class)->states('organizer')->create();

        $eventData = [
            'title' => 'Cache Test Event',
            'description' => 'Testing cache clear',
            'date' => now()->addWeeks(2)->format('Y-m-d H:i:s'),
            'location' => 'Test City',
        ];

        $this->actingAs($organizer, 'api')
            ->postJson('/api/events', $eventData);
    }

    /**
     * Test event update requires valid date.
     *
     * @return void
     */
    public function testEventUpdateRequiresValidDate()
    {
        $organizer = factory(User::class)->states('organizer')->create();
        $event = factory(Event::class)->create(['created_by' => $organizer->id]);

        $response = $this->actingAs($organizer, 'api')
            ->putJson("/api/events/{$event->id}", [
                'date' => now()->subDays(1)->format('Y-m-d H:i:s'),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }
}
