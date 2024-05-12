<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;
use App\Models\Photo;
use App\Models\CalbumCategory;
use DB;

class GalleryController extends Controller
{
    public function index(){
         
        $user = Album::latest()->with('user')->get();//user è il NOME DEL METODO DELLA RELAZIONE UNO A MOLTI nel model Album
                                                    //MI RITORNA LO USER CHE APPATIENE A QUELL'ALBUM!!
        /*$user = DB::table('users')
        ->select('users.name')
        ->leftjoin('albums', 'users.name', '=', 'albums.user_id')
        ->get();*/
        $albums = Album::latest()->with('categories')->get(); //categories è il NOME DEL METODO DELLA RELAZIONE MOLTI A MOLTI nel MODEL album
                                                    //MI RITORNANO LE CATEGORIE DI QUELL'ALBUM!!
        return view('gallery.albums')->with([
            'user' => $user ,
            'albums' => $albums
        ]);
        
    }
    public function showAlbumImages(Album $album){ //TYPE INT 

        //return Photo::whereAlbumId($album->id)->latest()->get();
        return view('gallery.image',
        [

            'images'=> Photo::where('album_id','=',$album->id)->latest()->get(),

            'album' => $album

        ]);
            
    }
   
    public function showAlbumsByCategory(CalbumCategory $category){ //TYPE INT
        return view('gallery.albums')->with('albums',
            $category->albums //albums è il NOME DEL METODO DELLA RELAZIONE MOLTI A MOLTI nel MODEL CalbumCategory
                            //MI RITORNANO GLI ALBUMS CHE APPARTENGONO A QUELLA CATEGORIA!!
        ); 

    }
}
