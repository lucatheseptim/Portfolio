<?php

use Illuminate\Database\Seeder;
use App\Models\AlbumsCategory; // importo il modello AlbumsCategory perche lo devo utilizzare 
use App\Models\CalbumCategory; // importo il modello CalbumCategory perche lo devo utilizzare 

class SeedAlbumTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(App\Models\Album::class ,10)->create()  //il metodo create mi ritorna una COLLECTION
        ->each(function($album){
            $cats = CalbumCategory::inRandomOrder()->take(3)->pluck('id');//CON PLUCK MI RITORNA SOLO LA COLONNA ID
            $cats->each(function ($cat_id) use($album){
                AlbumsCategory::create([
                    'album_id'=>$album->id,
                    'category_id'=>$cat_id
                ]);
            });
        });
    }
}
