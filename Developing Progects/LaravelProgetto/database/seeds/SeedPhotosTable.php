<?php

use Illuminate\Database\Seeder;
use App\Models\Album; //importo il modello Album perche lo devo utilizzare 

class SeedPhotosTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
       $albums = Album::get(); //prendo i dati con il queryBuilder 

       foreach ($albums as $album) {  //PER OGNI ALBUM VOGLIO 10 Foto

           factory(App\Models\Photo::class, 10)->create(

               ['album_id' => $album->id]  //'album_id' è il campo della tabella photos,
                                           //per ogni campo 'album_id' della tabella photos gli assegno l'id 

           );

       }
        //$photo = factory(App\Models\Photo::class ,60)->create();
    }
}
