<?php

namespace App\Http\Controllers\Auth;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use App\Mail\WelcomeMail;  //PER LA MAIL
use Illuminate\Support\Facades\Mail;  //PER LA MAIL

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|string'     //HO AGGIUNTO IL RUOLO
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        
        //REGISTRAZIONE E INVIO MAIL 
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role'=> $data['role']  //HO AGGIUNTO IL RUOLO
        ]);

        $subject = "My Gallery Album Registration";
        $message = "this WEB APP was created by Luca Airoldi with MVC Laravel with PHP and other languages PHP,JAVASCRIPT,BOOTSTRAP 4,HTML5,CSS3,
        Database: MYSQL
        
        Welcome To Web-App GALLERY ALBUM :)";
        //Mail::to($data['email'])->send(new WelcomeMail($user));
        mail($data['email'], $subject,$message);

        return $user;
        /*
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'role'=> $data['role']  //HO AGGIUNTO IL RUOLO
        ]);*/
       
    }

}
