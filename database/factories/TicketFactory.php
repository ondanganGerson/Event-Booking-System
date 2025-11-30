<?php

use Faker\Generator as Faker;

$factory->define(App\Ticket::class, function (Faker $faker) {
    $types = ['VIP', 'Standard', 'Premium', 'Early Bird', 'General Admission'];

    return [
        'type' => $faker->randomElement($types),
        'price' => $faker->randomFloat(2, 10, 500),
        'quantity' => $faker->numberBetween(50, 500),
        'event_id' => function () {
            return App\Event::inRandomOrder()->first()->id ?? factory(App\Event::class)->create()->id;
        },
    ];
});
