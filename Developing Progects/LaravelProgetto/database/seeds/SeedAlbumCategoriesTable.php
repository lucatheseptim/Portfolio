<?php

use Illuminate\Database\Seeder;
use App\Models\CalbumCategory;  //importo il modello CalbumCategory perchè lo devo utilizzare 

class SeedAlbumCategoriesTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cats = [     //array normale 

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
        foreach($cats as $cat){
            CalbumCategory::create(
                [
                    'category_name'=> $cat
                ]
                );
        }

    }
}
