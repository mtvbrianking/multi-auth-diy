<?php

use Faker\Generator as Faker;

// https://laravel.com/docs/7.x/database-testing#extending-factories

$factory->define(\Tests\Models\User::class, function (Faker $faker) {
    return factory(\App\User::class)->raw([
        // ...
    ]);
});
