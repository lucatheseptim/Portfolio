<?php

// BISOGNA SEMPRE METTERE DAVANTI admin  POI O /users O /dashboard OPPURE DI DEFAULT / PARTE CON 
//LA ROTTA ADMIN\AdminPanel perchè ho definito cosi la rotta nel Route Service Provider

Route::resource('/users','Admin\AdminUsersController',
    [
        'names'=>
        [
            'index' => 'user-list',  //FACCIO OVERRIDE di /users/index in /users/user-list
        ]
    ]

);
 
Route::get('/dashboard',function (){

    return "Admin Dashbaord";

});

Route::resource('/', 'Admin\AdminPanel');