<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlbumsCategory extends Model
{
    protected $table="album_category"; //SICCOME LA MIA TABELLA ha un nome al singolare
    //invece che al plurale ,DI DEFAULT DEVONO ESSERE TUTTE AL PLURALE (albums_category,photos,albums ecc)
    //devo dichirarla protected cosi' la mantengo al singolare 

}
