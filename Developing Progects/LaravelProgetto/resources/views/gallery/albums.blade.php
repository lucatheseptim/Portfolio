@extends('templates.layout')
@section('title','Gallery Album')
@section('content')
<div class="myform form ">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="logo mb-3">
            <h3>Login<i class="fa fa-user-circle-o" aria-hidden="true"></i><i class="fa fa-arrow-circle-up" aria-hidden="true"></i> or Register New User📝</h3>
        </div>
    </nav>
</div>
 <div class="col-md-10 text-center">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="alert alert-secondary" role="alert">
            <h1><p style="color:blue;">Users Gallery Album <i class="fa fa-picture-o" aria-hidden="true"></i></h1></p>
        </div>    
    </nav>
</div>
@php
$count = 0 ;
$countUser = 0;
@endphp
@foreach($albums as $album)
    @if($count==0)
        <div class="card-deck" style="width: 40rem;">
               
    @endif
    <div class="card">
        <b>
            User @php $countUser = $countUser +1;
            echo $countUser; @endphp Gallery Album
        </b>
        @php
        $cerca_parola = strpos($album->album_thumb,"https://lorempixel.com");
        @endphp
        @if($cerca_parola !==false) 
          
            @php
            if ( !function_exists( 'isSiteAvailible' ) ) {
                function isSiteAvailible($URL){
                    
                    
                // Initialize cURL
                $curlInit = curl_init($URL);
                
                // Set options
                curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,2); //2 secondi
                

                // Get response
                $response = curl_exec($curlInit);
                
                // Close a cURL session
                curl_close($curlInit);

                return $response?true:false;
                
                }
            }
        
            $URL = "https://lorempixel.com";
            @endphp
            @if(isSiteAvailible($URL))
                <a href="{{route('gallery.album.images',$album->id)}}"><img  height="250" title = "{{asset($album->album_name)}}" class="card-img-top" src="{{asset($album->album_thumb)}}" alt="{{asset($album->album_name)}}"></a>
            
                <div class="card-body">
                    <h4 class="card-title"><a href="{{route('gallery.album.images',$album->id)}}">{{$album->album_name}}</a></h4>

                    <p class="card-text">{{$album->description}}</p>
                    <p class="card-text">
                        Categories:
                        @foreach($album->categories as $cat) 
                            <a href="{{route('gallery.album.category',$cat->id)}}" >{{$cat->category_name}}</a>
                        @endforeach
                        <small class="text-mutex">{{$album->created_at->diffForHumans()}}</small>
                    </p>
                </div>     
            @else
                <div class="alert alert-warning" role="alert">
                    'Sorry , Current Lorempixel sites is
                    ⚠ DOWN no image available.....'
                </div>  
                <a href="{{route('gallery.album.images',$album->id)}}"><img  height="250" title = "no image " class="card-img-top"  alt="no image"></a>
                <div class="card-body">
                    <h4 class="card-title"><a href="{{route('gallery.album.images',$album->id)}}">{{$album->album_name}}</a></h4>

                    <p class="card-text">{{$album->description}}</p>
                    <p class="card-text">
                        Categories: 
                        @foreach($album->categories as $cat) 
                            <a href="{{route('gallery.album.category',$cat->id)}}" >{{$cat->category_name}}</a>
                        @endforeach
                        <small class="text-mutex">{{$album->created_at->diffForHumans()}}</small>
                    </p>
                </div>
            @endif
        @elseif ($cerca_parola == false) 
            <div class="alert alert-success" role="alert">  
                nuovo Album caricato da un Utente
            </div>
            <a href="{{route('gallery.album.images',$album->id)}}"><img  height="250" title = "{{asset($album->album_name)}}" class="card-img-top" src="{{url('uploads/'.$album->filename)}}" alt="{{asset($album->album_name)}}"></a>
            
            <div class="card-body">
                <h4 class="card-title"><a href="{{route('gallery.album.images',$album->id)}}">{{$album->album_name}}</a></h4>

                <p class="card-text">{{$album->description}}</p>
                <p class="card-text">
                    Categories:
                    @foreach($album->categories as $cat) 
                        <a href="{{route('gallery.album.category',$cat->id)}}" >{{$cat->category_name}}</a>
                    @endforeach
                    <small class="text-mutex">{{$album->created_at->diffForHumans()}}</small>
                </p>
            </div>
        @endif
    </div>
    @php
    $count++;
    @endphp
    @if($count == 2)
        </div> 
        @php
        $count = 0 ;
        @endphp
    @endif
@endforeach
@endsection





