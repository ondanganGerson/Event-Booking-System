<?php

use Faker\Generator as Faker;

$factory->define(App\Payment::class, function (Faker $faker) {
    return [
        'booking_id' => function () {
            return App\Booking::inRandomOrder()->first()->id ?? factory(App\Booking::class)->create()->id;
        },
        'amount' => $faker->randomFloat(2, 10, 1000),
        'status' => $faker->randomElement(['success', 'failed', 'refunded']),
    ];
});
