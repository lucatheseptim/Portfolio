@extends('templates.layout')
@section('title','Blog')
@section('content')

@component('components.card',
[
    'img_url'=>'http://lorempixel.com/400/200',
    'img_title'=>'First Image blog'
]
)
   <p>this is a beautiful picture i took in newyork </p> 
@endcomponent

@component('components.card')

    @slot('img_url','http://lorempixel.com/400/200/sports')
    @slot('img_title','second Image ')
  
   <p>this is a beautiful picture i took in swimming pool </p> 
@endcomponent
      
@endsection('content')

/*in questo modo ho il javascript nella pagina staff.blade.php*/
@section('footer')
    @parent

@endsection
