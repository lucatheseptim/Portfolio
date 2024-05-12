@extends('templates.layout')
@section('title','Create Album')
@section('content')
<div class="card">
    <h1>
        @if($photo->id)
        <h5 class="card-header info-color white-text text-center py-4">
            <strong style="color: #0000FF">Edit Photo Album</strong>
        </h5>
            @else
            <h5 class="card-header info-color white-text text-center py-4">
                <strong style="color: #0000FF">New Album Image</strong>
            </h5>
        @endif    
    </h1>
        @include('partials.inputerrors')
        @if($photo->id)
        <form action="{{route('photos.update',$photo->id)}}" method="POST" enctype="multipart/form-data">
                {{csrf_field() }}
                {{method_field('PATCH')}}
                <div class="form-group">
                    <label for="Name">Name</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{$photo->name}}"placeholder="Photo name">   
                </div>
                <div class="form-group">
                    <label for="Name">Albums</label>
                    <select  name="album_id" id="album_id">
                        <option value="">SELECT</option>
                        @foreach($albums as $item)
                            <option {{$item->id==$album->id ? 'selected':''}} value="{{$item->id}}">{{$item->album_name}}</option>
                        @endforeach
                    </select>
                </div>
                {{ csrf_field() }}
                @include('images.partials.fileuploadOrmodifyphoto')    
                <div class="form-group">
                    <label for="Description">Description</label>
                    <textarea name="description" id="description" class="form-control" 
                    placeholder="Photo description">{{$photo->description}}</textarea>
                </div>    
                <button type="submit" class="btn btn-outline-info btn-rounded btn-block my-4 waves-effect z-depth-0">Submit</button>
            </form>
            @else  
            <form action="{{route('photos.store')}}" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="Name">Name</label>
                    <input type="text"   name="name" id="name" class="form-control" value="{{$photo->name}}"placeholder="Photo name">   
                </div>
                <div class="form-group">
                    <label for="Name">Albums</label>
                    <select name="album_id" id="album_id">
                        <option value="">SELECT</option>
                        @foreach($albums as $item)
                            <option {{$item->id==$album->id ? 'selected':''}} value="{{$item->id}}">{{$item->album_name}}</option>
                        @endforeach
                    </select>
                </div>
                {{ csrf_field() }}
                @include('images.partials.fileupload')    
                <div class="form-group">
                    <label for="Description">Description</label>
                    <textarea name="description" id="description" class="form-control" 
                    placeholder="Photo description">{{$photo->description}}</textarea>
                </div>    
                <button type="submit" class="btn btn-outline-info btn-rounded btn-block my-4 waves-effect z-depth-0">Submit</button>
            </form>
        @endif
            
</div>
@endsection