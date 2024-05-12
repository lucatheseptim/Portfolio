<?php

use App\Models\Album;
use App\Models\Photo;
use App\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
/*
Route::get('/', function () {
    return view('welcome');
});
*/

Route::group(['middleware' => 'auth'], function () { //per proteggere le rotte protette dal middleware auth

	Route::get('/home','AlbumsController@index')->name('albums'); 

	Route::get('/about','AlbumsController@about')->name('albums.about');

	//Route::get('/','HomeController@index');

	/*passo alla mia rotta dei parametri che possono essere opzionali ,la funzione avra 3 parametri*/
	Route::get('welcome/{name?}/{lastname?}/{age?}','WelcomeController@welcome')
		->where([
			'name' => '[a-zA-Z]+',
			'lastname' => '[a-zA-Z]+',
			'age' => '[0-9]{1,3}'
		])
		; /*termino qui la route:get perchè metto il ; */
 
	Route::get('/album/user','AlbumsController@showAlbumUser')->name('album.show.albumimage');
		
	Route::get('/albums','AlbumsController@index')->name('albums');

	Route::get('/albums/{id}','AlbumsController@show1')->where('id','[0-9]+');	
	//Route::get('/albums/{album}','AlbumsController@show')->where('id','[0-9]+')->middleware('can:show,album');

	Route::get('/albums/create','AlbumsController@create')->name('album.create');

	//Route::delete('/albums/{id}','AlbumsController@delete')->where('id','[0-9]+')->name('album.delete');
	Route::delete('/albums/{album}','AlbumsController@delete')->where('album','[0-9]+')->name('album.delete');

	Route::patch('/albums/{id}','AlbumsController@store');

	Route::get('/albums/{id}/edit','AlbumsController@edit')->where('id','[0-9]+')->name('album.edit');

	Route::post('/albums','AlbumsController@save')->name('album.save');

	Route::get('/albums/{album}/images','AlbumsController@getImages')
	->name('album.getImages')
	->where('album','[0-9]+');

	Route::resource('categories','AlbumCategoryController');

	
		
	Route::get('usersnoalbums',function(){

		//LA QUERY IN MYSQL è la seguente:
		//SELECT u.id ,email , name , album_name 
		//FROM users as u
		//LEFT JOIN albums as a on u.id = a_user_id
		//WHERE album_name is null

		$usersnoalbums = DB::table('users as u')
		->leftjoin('albums as a','u.id','a.user_id')
		->select('u.id','email','name','album_name')->whereNull('album_name')
		->get();

		return $usersnoalbums;	
	});

	Route::get('/users',function(){

		/*return User::all();/*mi tornano tutti i dati*/
	});

	Route::resource('user','UserController');

	
});



Route::resource('photos','PhotosController'); 

								//ROTTE AUTENTICAZIONE 

Auth::routes(); //rotta per l'autenticazione dell'utente creata in automatico dal comando php artisan:make auth

//Route::get('/home', 'HomeController@index')->name('home'); //LARAVEL MI CREA IN AUTOMATICO QUESTA ROTTA da cui parte la mia HOME PAGE creata in automatico dal comando php artisan:make auth

								//ROTTE GALLERY
Route::group(['prefix' =>'gallery'], function () { //il percorso vuole un prefisso gallery/ e poi esempio albums

	Route::get('albums','GalleryController@index')->name('gallery.albums');
	Route::get('album/{album}/images','GalleryController@showAlbumImages')
	->name('gallery.album.images');
	Route::get('albums/category/{category}','GalleryController@showAlbumsByCategory')
	->name('gallery.album.category');
});

Route::get('/', 'GalleryController@index');


