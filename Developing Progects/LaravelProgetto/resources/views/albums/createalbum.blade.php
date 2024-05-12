@extends('templates.layout')
@section('title','Create Album')
@section('content')
<div class="col-md-8 col-md-offset-2">   
    <div class="card">
        <h5 class="card-header info-color white-text text-center py-4">
            <strong style="color: #0000FF">Create Album</strong>
        </h5>
        @include('partials.inputerrors')
        <form action="{{route('album.save')}}" method="POST" enctype="multipart/form-data">
            {{ csrf_field() }}  
            <div class="form-group">
                <label for="Name">Name</label>
                <input type="text" name="album_name" id="album_name" class="form-control" placeholder="Album name" value="{{old('album_name')}}"> 
               
            </div>
            @include('albums.partials.fileupload')    
            <div class="form-group">
                <label for="Description">Description</label>
                <textarea  name="description" id="description" class="form-control" 
                placeholder="Album description">{{old('description')}}</textarea>
            </div> 
            <div class="form-group">
                Categories:
                <select class="form-control" name="categories[]" id="categories" multiple size ="5">
                    @foreach($categories as $category)
                        <option {{in_array($category->id,$selectedCategories)? 'selected':''}} value="{{$category->id}}">{{$category->category_name}}</option>
                    @endforeach
                </select>
            </div>   
            <button type="submit" class="btn btn-outline-info btn-rounded btn-block my-4 waves-effect z-depth-0">
                Submit
            </button>
        </form>
    </div>
</div>
@endsection
