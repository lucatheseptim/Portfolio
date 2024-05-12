<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/
use App\Models\Album;  //importo il modello Album
use App\Models\Photo;  //importo il modello Photo 
use App\User;  //importo il modello User 


$cats = [   //array

    "abstract",
    "animals",
    "business",
    "cats",
    "city",
    "food",
   "nightlife",
   "fashion",
   "people",
   "nature",
    "sports",
    "technics",
    "transport",
];

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' =>$password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(App\Models\Album::class, function (Faker\Generator $faker) use($cats) {


    return [
        'album_name' => $faker->name,
        'description' => $faker->text(128),
        'user_id' => User::inRandomOrder()->first()->id, //cioe dammi tutti gli utenti e ordinameli in modo random  , first perche vogliano il primo id 
        'album_thumb'=> $faker->imageUrl(640,480,$faker->randomElement($cats))
    ];
});

$factory->define(App\Models\Photo::class, function (Faker\Generator $faker) use($cats) {


    return [
    
        'name' => $faker->text(64),
        'description' => $faker->text(128),
        'img_path' => $faker->imageUrl(640,480,$faker->randomElement($cats)), // a randomElement gli passo un array  
        //'album_id' => 1
        'album_id' => Album::inRandomOrder()->first()->id //cioe dammi tutti gli album e ordinameli in modo random  , first perche vogliano il primo id 
    ];
});
