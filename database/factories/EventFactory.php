<?php

use Faker\Generator as Faker;

$factory->define(App\Event::class, function (Faker $faker) {
    return [
        'title' => $faker->sentence(4),
        'description' => $faker->paragraph(3),
        'date' => $faker->dateTimeBetween('+1 week', '+3 months'),
        'location' => $faker->city . ', ' . $faker->state,
        'created_by' => function () {
            return App\User::where('role', 'organizer')->inRandomOrder()->first()->id ?? factory(App\User::class)->states('organizer')->create()->id;
        },
    ];
});
