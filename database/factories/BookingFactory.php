<?php

use Faker\Generator as Faker;

$factory->define(App\Booking::class, function (Faker $faker) {
    return [
        'user_id' => function () {
            return App\User::where('role', 'customer')->inRandomOrder()->first()->id ?? factory(App\User::class)->states('customer')->create()->id;
        },
        'ticket_id' => function () {
            return App\Ticket::inRandomOrder()->first()->id ?? factory(App\Ticket::class)->create()->id;
        },
        'quantity' => $faker->numberBetween(1, 5),
        'status' => $faker->randomElement(['pending', 'confirmed', 'cancelled']),
    ];
});
