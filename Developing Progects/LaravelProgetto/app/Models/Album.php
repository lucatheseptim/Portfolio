<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Photo; //importo il modello Photo
use App\User; //importo il modello User

class Album extends Model{

    //protected $table = 'Album'; //se la tabella ha un diverso nome da albums cioè al SINGOLARE
    //lo devo dichiarare $protected

    //protected $primaryKey ='id';
    protected $fillable = ['album_name','description','user_id'];//gli passo un array di valori 
    //con i campi che voglio proteggere quando gli inserisco

    public function getPathAttribute(){
        $url = $this->album_thumb;
        if(stristr($this->album_thumb,'http')===false){
            $url='storage/app/public/'.$this->album_thumb;
        }
       
        return $url;
    }

                                            //RELAZIONI 
    public function photos(){
        
        return $this->hasMany(Photo::class);  //RELAZIONE CON IL MODELS Photo ,UN ALBUM HA TANTE FOTO(1->n)
        //IL SECONDO PARAMETRO DOVREBBE ESSERE 'album_id' DI DEFAULT  e' la chiave esterna della tabella PHOTO che punta nella tabella ALBUM
        //SE NON è COSI' BISOGNA METTERE COME SECONDO PARAMETRO LA CHIAVE ESTERNA DELLA TABELLA FOTO
        //GUARDARE SU GUIDA ONLINE LARAVEL->relationships
    }

    public function categories(){

        return $this->belongsToMany(CalbumCategory::class,'album_category','album_id','category_id');
        //IL PRIMO PARAMETRO é LA CLASSE A CUI LA RELAZIONE molti a molti si riferisce
        //IL SECONDO PARAMETRO album_category è la tabella a cui punta 
        //IL TERZO PARAMETRO 'album_id' è foreign key che si riferisce alla tabella album
        //IL QUARTO PARAMETRO 'category_id' è foreign key che si riferisce alla tabella calbum_categories

    }
    public function user(){ //RELAZIONE CON User
        return $this->belongsTo(User::class); //IL PRIMO PARAMETRO é LA CLASSE A CUI LA RELAZIONE 
        
    }
}