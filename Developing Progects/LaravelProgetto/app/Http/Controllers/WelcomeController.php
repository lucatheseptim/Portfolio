<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; 

class WelcomeController extends Controller
{
    public function welcome($name='',$lastname='',$age=0 ,Request $req){
		
		$language = $req->input('lang'); 
		$res = '<h1>Hello World'.' '.$name.' '.$lastname.'you are '.$age.' years old , and your language is '.$language.'</h1>';	
		return $res;
	}
	
}
