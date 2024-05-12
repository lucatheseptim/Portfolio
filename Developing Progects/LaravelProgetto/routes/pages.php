<?php

Route::get('about','PageController@about'); /*bisogna mettere davanti pages/about perche ho dichiarato un metodo(mapPageRoutes) in RoteServiceProvider che aggiunge un prefisso (pages) a tutte le rotte */

Route::get('blog','PageController@blog'); /*bisogna mettere davanti pages/about perche ho dichiarato un metodo(mapPageRoutes) in RoteServiceProvider che aggiunge un prefisso (pages) a tutte le rotte */

Route::get('staff','PageController@staff'); /*bisogna mettere davanti pages/about perche ho dichiarato un metodo(mapPageRoutes) in RoteServiceProvider che aggiunge un prefisso (pages) a tutte le rotte */