<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use\App\Models\Albums;

class AlbumUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        //$album = Album::find($this->id);
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'album_name'=>'required|unique:albums,album_name',
            'description'=>'required',
            'bookcover'=>'required'
        ];
    }

    public function messages()//se voglio fare la traduzione dei messaggi devo chiamare questo METODO con questo nome
    {
        return [
            'album_name.required'=>'the album name field is required,please write the album name',
            'description.required'=>'the description field is mandatory,please write the description',
            'bookcover.required'=>'photo selection is mandatory,please select a photo '
            
        ];
    }
}
