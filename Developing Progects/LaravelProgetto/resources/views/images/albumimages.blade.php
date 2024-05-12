@extends('templates.layout')
@section('title','ALBUM IMAGES')
@section('content')
<a href="#" class="badge badge-secondary">
    <h3>Image For Album: {{$album->album_name}} </h3>
</a>
@if(session()->has('message')) 
    <div class="alert alert-info">{{session()->get('message')}}</div>
@elseif(session()->has('messageDelete')) 
    <div class="alert alert-info">{{session()->get('messageDelete')}}</div>
@endif
<form>
    <input type="hidden" name="_token" id="_token" value="{{ csrf_token() }}"> 
    <table class ="table table-bordered">
        
        <tr>
            <th>ID</th>

            <th>CREATED DATE</th>

            <th>TITLE</th>

            <th>ALBUM</th>

            <th>THUMBNAIL</th>
            <th class="text-center"><i class="fas fa-cogs"></i></th>
        </tr>    
        @forelse($images as $image)
            <tr> 
                <td>{{$image->id}}</td>
                <td>{{$image->created_at->format('d/m/Y H:i')}}</td>
                <td>{{$image->name}}</td>
                <td><a href="{{route('album.edit',$image->album_id)}}">{{$album->album_name}}</a></td>
                <td>
                    <div class="card">
                        Photo Image:
                        <img width="300" src="{{asset($image->path)}}">
                        <img class="card-img-top" src="{{url('uploads/'.$image->filename)}}" alt="{{$image->filename}}">
                        <div class="card-body">
                            <h4 class="card-title">Photo No: {{ $image->id}}</h4>
                            <p class="card-text">
                                Photo name:<strong>{{ $image->name}}</strong>
                            </p>
                        </div>
                    </div>
                </td>
                <td>
                    <a href="{{route('photos.edit',$image->id)}}" class="btn btn-sm btn-primary">MODIFY<span class="fa fa-pencil"></span></a>
                    <a href="{{route('photos.destroy',$image->id)}}" class="btn btn-sm btn-danger">DELETE<span class="fa fa-minus"></span></a>
                </td>
            </tr>
        @empty 
                <tr><td colspan="5">
                    no Images found...
                </tr></td>    
        @endforelse
    </table> 
    <a href="{{route('albums')}}" class="btn btn-default">Back To Albums</a>
    <tr>
            <td colspan="6" class="text-center">
                <div class="row">
                    <div class="col-md-8 push-2">
                        {{ $images->links('vendor.pagination.bootstrap-4') }}
                    </div>
                </div>
            </td>
    </tr>
</form>
@endsection
@section('footer')
    @parent
    <script>
        $('document').ready(function(){

            $('table').on('click','a.btn-danger',function(elementclicked){

                elementclicked.preventDefault(); 
                
                var urlImg  = $(this).attr('href'); 
                var tr = elementclicked.target.parentNode.parentNode;
       
                $.ajax(urlImg,
                    {
                   
                      method:'DELETE', 
                      data:{
                        '_token': $('#_token').val()
                      },
                      complete : function(resp){ 
                         
                          if(resp.responseText == 1){
                              tr.parentNode.removeChild(tr); 
                              
                          }else{
                              alert('Problemi contattando il server');
                          }
                      }
                    }
            
                )
            }); 
        });
       
    </script>  
@endsection
