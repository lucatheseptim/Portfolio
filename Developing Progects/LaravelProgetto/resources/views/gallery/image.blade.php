@extends('templates.layout')
@section('title','Gallery Images Album')
@section('content')
<div class="row">
    @foreach($images as $image)
        <h5>{{$image->id}}</h5>
        @php 
            $cerca_parola = strpos($image->img_path,"https://lorempixel.com");
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
                <div class="col-md-4 col-sm-6 col-lg-2">

                    <a href="{{asset($image->img_path)}}" data-lightbox="{{$album->album_name}}">

                        <img class="img-fluid img-thumbnail" width="250" alt="{{$image->name}}"  src="{{asset($image->img_path)}}"></a>

                </div>    
            @else
                <div class="alert alert-warning" role="alert">
                    'Sorry , Current Lorempixel sites is
                    ⚠ DOWN no image available.....'
                </div>  
                <div class="col-md-4 col-sm-6 col-lg-2">
                    <img class="img-fluid img-thumbnail" width="250" title="no image" >
                </div> 
            @endif
        @elseif ($cerca_parola == false)
                <div class="col-md-4 col-sm-6 col-lg-2">

                    <a href="{{url('uploads/'.$image->filename)}}" data-lightbox="{{$album->album_name}}">

                    <img class="img-fluid img-thumbnail" width="250" src="{{url('uploads/'.$image->filename)}}" ></a>
                    <div class="card-body">
                        <p class="card-text">
                            Photo name:<strong>{{ $image->name}}</strong>
                        </p>
                    </div>
                </div>
        @endif
    @endforeach
</div>

@endsection 