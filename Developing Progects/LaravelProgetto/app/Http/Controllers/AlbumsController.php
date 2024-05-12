<?php

namespace App\Http\Controllers;

use Auth;  //per l'autorizzazione
use Illuminate\Http\Request;
//use Illuminate\Database\Eloquent\ModelNotFoundException;
//use App\Events\NewAlbumCreated;
use App\Models\Album;
use App\Models\Photo;
use App\Models\CalbumCategory;
use App\Http\Requests\AlbumRequest;  //importo la classe per validazione dei campi dell'Album
use App\Http\Requests\AlbumUpdateRequest; 
use App\Policies\AlbumPolicy;    //importo la classe per Policy dell'Album
use function returnArgument;
use function config;
use DB; //per le query senza il query Builder
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;



class AlbumsController extends Controller
{
    
    public function __costruct(){

        /*$this->middleware('auth');
        $this->middleware('auth')->only('create','edit');*/
    }
    

    public function index(Request $request){
    
     
    //Ho sostituito DB::table('albums')->    con Album::orderBy('id','DESC') (il primo metodo deve essere statico )
    $eloquentIndex = Album::orderBy('id','ASC')->withCount('photos');

    $eloquentIndex->where('user_id',Auth::user()->id); //id dell'utente OPPURE $queryBuilder->request()->user()->id

    if($request->has('id')){ //se ho dalla pagina web una richiesta di un id esempio: albums/12/
        $eloquentIndex->where('id','=',$request->input('id'));
    }
    if($request->has('album_name')){
        $eloquentIndex->where('album_name','like','%'.$request->input('album_name').'%');
    }
    $eloquentIndex->groupBy('album_name'); 
    
    //$albums = $queryBuilder->get(); //Quando uso Eloquent devo richiamare alla fine il get() perchè mi ritorna una Collection
    $albums = $eloquentIndex->paginate(1); // 1 per pagina QUANDO USO PAGINATE SOSTITUISCE IL ->get()
    return view('albums.albums',['albums'=>$albums]);

    

    //METODO UTILIZZANDO LA FACADE DB

    /*return Album::all();  */
    /*
    $sql = 'select * from albums where 1=1';  /*where 1=1 per selezionare tutti i valori*/
    /*
    $where = [];
    if($request->has('id')){
        $where['id']=$request->get('id');
        $sql.=" AND id=:id";  /*chiave =segnaposto*/
    /*} 
    /*   
    if($request->has('album_name')){
        $where['album_name']=$request->get('album_name');
        $sql.="AND album_name =:album_name";
    } 
    /* dd($sql); */
    
    /*return DB::select($sql,$where);*/
    /*
    $albums = DB::select($sql,$where);

    return view('albums.albums',['albums'=>$albums]);
    */

    }

    public function delete(Album $album) //TYPE INT 
    {
        //Soluzione 1
        //ho sostituito  DB::table('albums')->   con  Album::where('id','=',$id) 
        //$eloquentDelete = Album::where('id','=',$id)->delete();
        //return $eloquentDelete;


        //Soluzione 2 con Storage
       // $this->authorize('delete',$album); //RICHIAMA LA POLICY AlbumPolicy
        $thumbNail = $album->album_thumb;
        $disk = config('filesystem.default');
        $eloquentDelete = $album->delete();  //ritorna true o false
        if($eloquentDelete){ //se è true 
            $this->deleteFile($thumbNail,$disk);
        }

        //return ''.$eloquentDelete; mi ritorna 1
        if(request()->ajax()) {

            $messaggioDelete = $eloquentDelete ? 'album deleted': 'album not deleted';

            session()->flash('messageDelete',$messaggioDelete);

            return '' . $eloquentDelete;

        } else {

 
           return  redirect()->route('albums');

        }




        //METODO UTILIZZANDO LA FACADE DB

        //$sql = 'delete from albums where id =:id';
        //return DB::delete($sql, ['id'=>$id]);
        //return redirect()->back(); /*torna indietro */ 
    }
    public function show(Album $album) //TYPE INT 
    {
        echo'Show';
        dd($album);
    }
    public function show1($id)
    {

        $album = Album::find($id);
        dd($album);
        //METODO UTILIZZANDO LA FACADE DB
        /*
        $sql ='select * from albums where id =:id';
        return DB::select($sql, ['id'=>$id]);
        //return redirect()->back(); /*torna indietro */
    }

    public function edit($id)
    {

      $album = Album::find($id);

      $this->authorize($album); //RICHIAMA LA POLICY AlbumpPolicy 
      $categories = CalbumCategory::get();
      $selectedCategories = $album->categories->pluck('id')->toArray();


      /*if($album->user->id !== Auth::user()->id){
          abort(401,'Unauthorized');
      }*/
      return view('albums.editalbum')->with(
          [
              'album'=>$album,
              'categories'=>$categories,
              'selectedCategories'=> $selectedCategories
          ]);

      //METODO UTILIZZANDO LA FACADE DB
      /*  
      $sql ='select id,album_name,description,id from albums where id =:id';
      $album = DB::select($sql,['id'=>$id]);
      return view('albums.editalbum')->with('album',$album[0]); /*a with gli passo come primo parametro 
      il nome della variabile e come secondo parametro  il valore della variabile ,
      metto lo[0] perchè è un array di array e a me interessa il primo array dentro all'array/
     
     /* return view('albums.editalbum',['album'=>$album[0]]);  fuziona anche cosi'*/
    }
    
    public function store($id, AlbumUpdateRequest $req)//utilizzo Request con i dati ricevuti dal Form
    {
        //PRIMO METODO
        //ho sostituito DB::table('albums')->    con   Album::where('id',$id) 
        /*
        $queryBuilderStore = Album::where('id','=',$id)->update(
            [
                'album_name'=> request()->input('name'), // SI PUO FARE ANCHE COSI: $req->name oppure $req->input('name')
                'description'=> request()->input('description')
            ]
        );
        */
        //SECONDO METODO 
        //salvataggio immagine 
        $cover = request()->file('bookcover');
        $extension = $cover->getClientOriginalExtension();
        Storage::disk('public')->put($cover->getFilename().'.'.$extension,  File::get($cover));
        //fine salvataggio immagine
        $album = Album::find($id);
        //$this->authorize('update',$album); //RICHIAMA LA POLICY AlbumPolicy
        $album->album_name = request()->input('album_name'); //richiamo l'input 
        $album->description = request()->input('description'); //richiamo la descrizione
        $album->user_id = $req->user()->id; //prendo L'user 
        $album->mime = $cover->getClientMimeType();
        $album->original_filename = $cover->getClientOriginalName();
        $album->filename = $cover->getFilename().'.'.$extension;
        $album->album_thumb ="";
        //$this->processFile($id,$req,$album);
        $eloquentStore = $album->save(); //ritorna true o false
        $album->categories()->sync($req->categories);//USO Sync per RIMPIAZZARE le categorie correnti con le nuove 
     
        $messaggio = $eloquentStore ? 'Album with ID= '.$id.' '.'updated' : 'Album with ID= '.$id.' '.'non updated';

        session()->flash('message',$messaggio);  //è un HELPER ,sto dicendo che nella sessione attuale definisco un messaggio 
       
        return redirect()->route('albums');
        


        //METODO UTILIZZANDO LA FACADE DB

        //FILTRO I RISULTATI ,visualizzo solo il nome,descrizione,id 
        //DEL FORM INVIATO tramite la richiesta via POST
       /* 
       $data=request()->only(['name','description']);  
       $data['id']=$id;
       /* dd($data);*/
       /*
       $sql= 'update albums set album_name =:name, description =:description ';
       $sql.= 'where id =:id';
       $res = DB::update($sql,$data);
       /*dd($res); //se è andato a buon fine mi ritorna 1 */
       /*
       $messaggio= $res ? 'Album con ID= '.$id.' '.'aggiornato' : 'Album con ID= '.$id.' '.'non aggiornato';

       session()->flash('message',$messaggio);  //è un HELPER ,sto dicendo che nella sessione attuale definisco un messaggio 
       
       return redirect()->route('albums');
       */
       
    }
    
    public function create()
    {
        $album = new Album(); //lo istanzio vuoto
        $categories = CalbumCategory::get(); //prendo tutte le CATEGORIE DELL'ALBUM
        $selectedCategories = $album->categories->pluck('id')->toArray();

        return view('albums.createalbum',['album'=>$album ,'categories'=>$categories,'selectedCategories'=>$selectedCategories]);
    }

    public function save(AlbumRequest $req)
    {
        //ho sostituito DB::table('albums')->    con Album::create 

        //PRIMO METODO 
        /*
        $queryBuilderSave = Album::create(
            [
                'album_name'=> request()->input('name'),
                'description'=> request()->input('description'),
                'user_id'=> 1
            ]
        );
        */
        //SECONDO METODO
        //salvataggio immagine 
        $cover = request()->file('bookcover');
        $extension = $cover->getClientOriginalExtension();
        Storage::disk('public')->put($cover->getFilename().'.'.$extension,  File::get($cover));
        //fine salvataggio immagine
        $album = new Album(); //istanzio un nuovo Album
        //$this->authorize('update',$album); //RICHIAMA LA POLICY AlbumPolicy
        $album->album_name = request()->input('album_name'); //richiamo l'input 
        $album->description = request()->input('description'); //richiamo la descrizione
        $album->user_id = $req->user()->id; //prendo L'user 
        $album->album_thumb ="";
        $album->mime = $cover->getClientMimeType();
        $album->original_filename = $cover->getClientOriginalName();
        $album->filename = $cover->getFilename().'.'.$extension;
        $eloquentSave = $album->save(); //ritorna true o false
        if($eloquentSave){
            //event(new NewAlbumCreated($album));
            if($req->has('categories')){
                $album->categories()->attach($req->categories);// USO attach per aggiungere le categorie nella Pivot Table(album_category) 
            }
            /*if($this->processFile($album->id,request(),$album)){
                $album->save();
            }*/
        }
        $name = request()->input('name');

        $messaggio = $eloquentSave ? 'Album '.$name.' '.'created' : 'Album '.$name.' '.'not created';

        session()->flash('message',$messaggio);  //è un HELPER ,sto dicendo che nella sessione attuale definisco un messaggio 
        
        return redirect()->route('albums');
    


        //METODO UTILIZZANDO LA FACADE DB

        /*
        $data=request()->only(['name','description']);
        $data['user_id']= 1;
        $sql= 'insert into albums (album_name,description,user_id)';
        $sql.='values (:name, :description,:user_id)';
        $res = DB::insert($sql,$data);
        $messaggio= $res ? 'Album '.$data['name'].' '.'creato' : 'Album '.$data['name'].' '.'non creato';

        session()->flash('message',$messaggio);  //è un HELPER ,sto dicendo che nella sessione attuale definisco un messaggio 
        return redirect()->route('albums');

        /*dd($request->all());*/
    }

    public function processFile($id,Request $req,&$album){
        if(!$req->hasfile('album_thumb')){
            return false;
        }
        $file = $req->file('album_thumb');
        if(!$file->isValid()){
            return false;
        }
        //$filename = $file->store(env('ALBUM_THUMB_DIR'));
        $filename = $id.'.'.$file->extension();
        $file->storeAs(env('ALBUM_THUMB_DIR'),$filename);
        $album->album_thumb = env('ALBUM_THUMB_DIR').$filename;
        return true;
    }

    public function getImages(Album $album){ //TYPE INT 

        //$images = PHOTO::where('album_id',$album->id)->get();
        $images = PHOTO::where('album_id','=',$album->id)->paginate(3);
        //return view('images.albumimages',['album'=>$album,'images'=>$images]);
        return view('images.albumimages',compact('album','images')); 
    }

    public function deleteFile($thumbNail,$disk){
        if($thumbNail && Storage::disk($disk)->has($thumbNail)){
            Storage::disk($disk)->delete($thumbNail);
        } 
    }

    public function about(){
        return view('albums.about');
    }
 
    public function showAlbumUser(){

        $albumsUser = Album::orderBy('id','ASC');

        $albumsUser->where('user_id',Auth::user()->id);

        $albums = $albumsUser->paginate(1);  // visualizzo 1 album per pagina SOSTITUISCE IL ->GET()

        return view('albums.useralbum',['albums'=> $albums]);

    }

 
}