<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\User; //importo il modello User 


class Photo extends Model
{

    public function getPathAttribute(){
        $url = $this->img_path;
        if(stristr($url,'http')===false){
            $url ='storage/'.$url;
        }
        return $url;
    }

    public function album(){

        return $this->belongsTo(Album::class);
    }


    
}
