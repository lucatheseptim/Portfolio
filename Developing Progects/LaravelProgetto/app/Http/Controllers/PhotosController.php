<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Photo;
use App\Models\Album;
use Auth;  //per l'autorizzazione
use function config;
use function returnArgument;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class PhotosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $rules = [        // regole
        'album_id'=>'required|integer|exists:albums,id',
        'name'=>'required|unique:photos,name',
        'description'=>'required',
        'bookcover'=>'required'
        
    ];

    protected $errorMessages = [
        'album_id.required'=>'the id field is required',
        'name.required'=>'the album name field is required,please write the album name ',
        'description.required'=>'the description field is mandatory,please write the description',
        'bookcover.required'=>'photo selection is mandatory,please select a photo'
       
    ];

    public function __costructor(){

        $this->middleware('auth');
        //$this->authorizeResource('Photo::class');

    }

    public function index()
    {
        return Photo::get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $req)
    {

        
        $id = $req->has('album_id')?$req->input('album_id'):null;  //verifico se mi arriva dal Browser un Id
        $album = Album::firstOrNew(['id'=>$id]); //prendo l'id dell'album passato tramite Get e controllo che ci sia 

        $photo = new Photo(); //la istanzio 
        $albums = $this->getAlbums(); //prendo tutti gli album
        return view('images.editimages',compact('albums','album','photo'));
        
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request,$this->rules,$this->errorMessages); //valido i campi passandogli delle regole

        $cover = $request->file('bookcover');
        $extension = $cover->getClientOriginalExtension();
        Storage::disk('public')->put($cover->getFilename().'.'.$extension,  File::get($cover));
        $photo = new Photo();
        $photo->name = $request->input('name');
        $photo->description = $request->input('description');
        $photo->album_id = $request->input('album_id');
        $photo->mime = $cover->getClientMimeType();
        $photo->original_filename = $cover->getClientOriginalName();
        $photo->filename = $cover->getFilename().'.'.$extension;
        //$this->processFile($photo);
        $photo->save();
        return redirect(route('album.getImages', $photo->album_id));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Photo $photo)
    {
        $albums = $this->getAlbums();
        $album = $photo->album;  //album è il NOME DEL METODO DELLA RELAZIONE UNO A MOLTI nel MODEL Photo
                                //STO DICENDO CHE VOGLIO VEDERE GLI ALBUM CHE APPARTENGONO A QUELLA FOTO
                                // O LA FOTO CHE APPARTIENE A QUELL' ALBUM 
                                //MI RITORNA L'ALBUM CHE APPARTIENE A QUELLA PHOTO!!
        return view('images.editimages',compact('album','albums','photo'));
       
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Photo $photo)
    {
        $this->validate($request,$this->rules,$this->errorMessages); //valido i campi passandogli delle regole

        $cover = $request->file('bookcover');
        $extension = $cover->getClientOriginalExtension();
        Storage::disk('public')->put($cover->getFilename().'.'.$extension,  File::get($cover));
        $photo->mime = $cover->getClientMimeType();
        $photo->original_filename = $cover->getClientOriginalName();
        $photo->filename = $cover->getFilename().'.'.$extension;
        //$this->processFile($photo);
        $photo->album_id = $request->album_id;
        $photo->name = $request->input('name');
        $photo->description = $request->input('description');
        $eloquentUpdate = $photo->save(); // mi ritorna true o false
 
        $messaggio = $eloquentUpdate ? 'Image '.$photo->name.' '.'updated' : 'Image '.$photo->name.' '.'not updated';
 
        session()->flash('message',$messaggio);  //è un HELPER ,sto dicendo che nella sessione attuale definisco un messaggio 
       
        return redirect(route('album.getImages', $photo->album_id));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Photo $photo)
    {
        $res = $photo->delete(); //mi ritorna true o false 
        if($res){
            $this->deleteFile($photo);
            
            $messaggio = $res ? 'Image '.$photo->name.' '.'cancelled' : 'Image '.$photo->name.' '.'not cancelled';

            session()->flash('messageDelete',$messaggio);  //è un HELPER ,sto dicendo che nella sessione attuale definisco un messaggio 
            
        }
        return ''.$res;
    }

    public function processFile(Photo $photo,Request $req=null){
        if(!$req){
            $req=request();
        }
        if(!$req->hasfile('img_path')){
            return false;
        }
        $file = $req->file('img_path');
        if(!$file->isValid()){
            return false;
        }
   
        //$filename = $file->store(env('ALBUM_THUMB_DIR'));
        $imgName = preg_replace("@\W@",'_', $photo->name);
        $filename = $imgName. '.' . $file->extension();
        $file->storeAs(env('IMG_DIR').'/'.$photo->album_id,$filename);
        $photo->img_path = env('IMG_DIR').$photo->album_id.'/'.$filename;
        return true;
    }

    public function deleteFile(Photo $photo){
        $disk = config('filesystem.default');
        if($photo && Storage::disk($disk)->has($photo)){
            return Storage::disk($disk)->delete($photo);
        } 
        return false;
    }

    
    public function getAlbums(){
        //where('user_id',Auth::user()->id)
        return Album::orderBy('album_name')
        ->get();
    }
}
