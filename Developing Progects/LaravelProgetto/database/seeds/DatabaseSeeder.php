<?php

use Illuminate\Database\Seeder;
use App\Models\Photo; //importo il modello photo 
use App\Models\Album;  //importo il modello Album
use App\Models\CalbumCategory; //importo il modello CalbumCategory
use App\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         DB::statement('SET FOREIGN_KEY_CHECKS=0;'); /*per disattivare le chiavi esterne*/
         User::truncate(); //elimino i dati senza per questo eliminare l’intera tabella
         CalbumCategory::truncate(); //elimino i dati senza per questo eliminare l’intera tabella
         Album::truncate(); //elimino i dati senza per questo eliminare l’intera tabella
         Photo::truncate(); //elimino i dati senza per questo eliminare l’intera tabella
         $this->call(SeedUserTable::class); 
         $this->call(SeedAlbumCategoriesTable::class);
         $this->call(SeedAlbumTable::class);
         $this->call(SeedPhotosTable::class);
    }
}
