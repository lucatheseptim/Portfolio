<div class="form-group"> 
    <label for="Thumbnail">Choose Photo Image:
        <div class="alert alert-warning" role="alert">
            *Please re-upload the same Photo Image or upload new Photo Image
        </div>   
    </label>
    <input type="file" class="form-control" name="bookcover"/>
</div>
@if($photo->filename !=null)
    <div class="col-md-6 offset-md-3 book-desc">
        <div class="card">
            Photo Image
            <img class="card-img-top" src="{{url('uploads/'.$photo->filename)}}" alt="{{$photo->filename}}">
        </div>
    </div>
@endif