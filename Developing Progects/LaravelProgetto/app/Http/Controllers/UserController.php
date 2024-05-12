<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Http\Requests\UserRequest;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function _costructor(){

       

    }
    
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::find($id);

        return view('user.edituser',['user' => $user]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $req, $id)
    {
        $pwd = $req->input('newpassword');
        $conf = $req->input('confirmpassword');
        if($pwd==$conf)
        {
            $user = User::find($id);
            $user->name = $req->input('name');
            $passwordEncript = bcrypt($pwd);  //cripto la password 
            $user->password = $passwordEncript;
            $user->email = $req->input('email');
            $user->role = $req->input('role');
            $save_user = $user->save();

            $messaggio = $save_user ? 'utente'.'   '.$user->name.'   '. 'aggiornato' : 'utente'.'   '.$user->name.'  '. 'non aggiornato';

            session()->flash('UserUpdate',$messaggio); 

            return redirect()->route('albums');
        }
        else{
            $messaggio = 'la nuova password non coincide con quella confermata, si prega di ricontrollare.... ';

            session()->flash('UserPassword',$messaggio); 

            return redirect()->route('albums');
        }
        
      
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {

        

        $userDelete = $user->delete();
        
        $messaggio = $userDelete ? 'utente'.'   '.$user->name.'   '. 'cancellato' : 'utente'.'   '.$user->name.'  '. 'non cancellato';

        session()->flash('UserDelete',$messaggio); 

        return redirect()->route('albums');
    }
}
