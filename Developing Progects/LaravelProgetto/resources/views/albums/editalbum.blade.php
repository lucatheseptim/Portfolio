@extends('templates.layout')
@section('title','Edit Album')
@section('content')
<div class="col-md-8 col-md-offset-2">   
    <div class="card">
        <h5 class="card-header info-color white-text text-center py-4">
            <strong style="color: #0000FF">Edit Album</strong>
        </h5>
        @include('partials.inputerrors')
        <form action="/albums/{{$album->id}}" method="POST" enctype="multipart/form-data">
            {{ csrf_field() }} 
            <input type="hidden" name="_method" value="PATCH">
            <div class="form-group">
                <label for="Name">Name</label>
                <input type="text" name="album_name" id="album_name" class="form-control" value="{{old('album_name',$album->album_name)}}"  placeholder="Album name">   
            </div> 
            @include('albums.partials.fileuploadOrmodifyimage')
            <div class="form-group">
                Categories:
                <select class="form-control" name="categories[]" id="categories" multiple size ="5">
                    @foreach($categories as $category)   
                        <option {{in_array($category->id,$selectedCategories)? 'selected':''}} value="{{$category->id}}">{{$category->category_name}}</option>
                    @endforeach
                </select>
            </div> 
            <div class="form-group">
                <label for="Description">Description</label>
                <textarea name="description" id="description" class="form-control" 
                placeholder="Album description">{{old('description',$album->description)}}</textarea>
            </div>    
            <button type="submit" class="btn btn-outline-info btn-rounded btn-block my-4 waves-effect z-depth-0">Submit</button>
            <a href="{{route('albums')}}" class="btn btn-default">Back</a>
            <a href="{{route('album.getImages',$album->id)}}" class="btn btn-success">Album Images</a>
        </form>
    </div>
</div>
@endsection
