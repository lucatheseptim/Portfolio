<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true ;
    }

    public function rules(){
        
        return[
            'name'=>'required',
            'email'=>'required',
            'newpassword'=>'required',
            'confirmpassword'=>'required'
            ];
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'name.required'=>'il campo nome è obbligatorio',
            'email.required'=>'il campo email è obbligatorio',
            'newpassword.required'=>'il campo nuova password è obbligatorio',
            'confirmpassword.required'=>'il campo conferma password è obbligatorio'
        ];
    }
}
