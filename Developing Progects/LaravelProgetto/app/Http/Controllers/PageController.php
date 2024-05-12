<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
	/*array di dati*/
	protected $data=[
		[
			'name'=>'luca',
			'lastname'=>'airoldi'
		],
		[
			'name'=>'hidran',
			'lastname'=>'Sarias'
		],
		[
			'name'=>'Harry',
			'lastname'=>'Plotter'
		],
		[
			'name'=>'James',
			'lastname'=>'down'
		]
		
	];
	

	
    public function about(){
		return view('about');
	}
	
	public function blog(){
		return view('blog');
	}
	
	public function staff(){
		return view('staff',['title'=>'our staff','staff'=>$this->data]); /*oppure 'staff'=>$data*/
	}
}
