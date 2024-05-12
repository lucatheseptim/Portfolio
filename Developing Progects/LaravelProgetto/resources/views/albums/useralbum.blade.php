@extends('templates.layout')
@section('content')
@section('title','User Album')
<div class="col-md-10 text-center">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="alert alert-secondary" role="alert">
            <h1><p style="color:blue;">Your Albums <i class="fa fa-picture-o" aria-hidden="true"></i></h1></p>
        </div>    
    </nav>
</div>
    @forelse ($albums as $album)
            <div class="card" style="width: 16rem;">
                <a href="{{url('uploads/'.$album->filename)}}" data-lightbox="{{$album->album_name}}">
                    <img class="img-fluid img-thumbnail" width="250" src="{{url('uploads/'.$album->filename)}}" ></a>
                <div class="card-body">
                    <h6 class="card-title">
                            album name: 
                        {{$album->album_name}}
                    </h6>
                </div>
            </div>
            <div class="col-md-8 push-2">
                {{$albums->links('vendor.pagination.bootstrap-4')}}
            </div>
        @empty
        <div class="alert alert-warning" role="alert">
           Sorry you have no Album ,please create one
           <a href="{{route('album.create')}}">Create Album</a> 
        </div>  
    @endforelse
@endsection