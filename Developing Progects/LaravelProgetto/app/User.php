<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Album; //importo il modello Album
use App\Models\CalbumCategory; //importo il modello CalbumCategory
class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ //PER LA REGISTRAZIONE HO AGGIUNTO I CAMPI 'surname' e 'telefono'
        'name', 'email', 'password', 'role','surname','telefono'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function albums(){  //RELAZIONE CON il MODELS Album , un user ha uno o piu' album(1->n)

       return  $this->hasMany(Album::class);
       
    }

    
    public function albumCategories(){ //RELAZIONE CON il MODELS CalbumCategory , un user puo' 
        //appartenere a una o piu' categorie 

        return  $this->hasMany(CalbumCategory::class);
        
     }

    public function getFullNameAttribute(){ // funzione che mi ritorna il nome dell'utente 

        return $this->name;
    }

    public function isAdmin(){
        
        return $this->role === 'admin';
    }


}
