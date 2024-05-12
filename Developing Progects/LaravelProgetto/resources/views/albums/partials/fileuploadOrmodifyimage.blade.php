<div class="form-group">
    <label for="Thumbnail">Choose Album Image :
        <div class="alert alert-warning" role="alert">
            *Please re-upload the same Album Image or upload new Album Image
        </div>
    </label>
    <input type="file" class="form-control" name="bookcover"/>
</div>
@if($album->filename !=null)
    <div class="col-md-6 offset-md-3 book-desc">
        <div class="card">
            Image Album:
            <img class="card-img-top" src="{{url('uploads/'.$album->filename)}}" alt="{{$album->filename}}">
            <div class="card-body">
                <h4 class="card-title">Album No: {{ $album->id}}</h4>
            </div>
        </div>
    </div>
@endif