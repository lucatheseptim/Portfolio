<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User; //importo il modello User
use Illuminate\Database\Eloquent\Builder; //richiamo il Query Builder 

class CalbumCategory extends Model
{
    
    public function albums(){
    
        return $this->belongsToMany(Album::class,'album_category','category_id','album_id');

    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    //METODO CON IL QUERY BUILDER
    public function scopeGetCategoriesByUserId(Builder $queryBuilder,User $user){

        $query = $queryBuilder->where('user_id',$user->id)->withCount('albums')->latest()->paginate(5);
        return $query;
    }
}
 