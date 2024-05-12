@extends('templates.layout')
@section('content')
        <h5 class="card-header info-color white-text text-center py-4">
            <strong style="color: #0000FF">Create Album</strong>
        </h5>    
        @if(session()->has('message')) 
            <div class="alert alert-info">{{session()->get('message')}}</div>
            @elseif(session()->has('messageDelete')) 
            <div class="alert alert-info">{{session()->get('messageDelete')}}</div>
            @elseif(session()->has('UserUpdate'))
            <div class="alert alert-info ">{{session()->get('UserUpdate')}}</div>
            @else(session()->has('UserPassword'))
            <div class="alert alert-danger ">{{session()->get('UserPassword')}}</div>
        @endif
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Album name</th>
                        <th>Thumb</th>
                        <th>Creator</th>
                        <th>Categories</th>
                        <th>Created Date</th>
                        <th>Manage</th>
                    </tr>
                </thead>
                @foreach($albums as $album)
                <tr id="tr{{$album->id}}">
                    <td> {{$album->id}} {{$album->album_name}}</td>
                    <td>
                            Image Album:
                            <img width ="150" height="150" src="{{url('uploads/'.$album->filename)}}" alt="{{$album->filename}}">
                    </td>
                    <td>
                        {{$album->user->fullname}}
                    </td>
                    <td>
                        @if($album->categories->count())
                        <ul>
                            @foreach($album->categories as $category)
                                <li>{{$category->category_name}} {{$category->id}}</li>
                            @endforeach

                        </ul>
                            @else
                            No Categories found....
                        @endif
                    </td>
                    <td>{{$album->created_at->format('d/m/Y H:i')}}</td>
                    <td>
                        <div class="row">
                           
                                <a title="Add picture" href="{{route('photos.create')}}?album_id={{$album->id}}" class="btn btn-sm btn-success">
                                    <span class ="fa fa-plus-square"> New Image</span>
                                </a>                        
                                @if($album->photos_count)
                                    <a title="View Images" href="{{route('album.getImages',$album->id)}}"
                                        class="btn btn-default">
                                        <span class="fa fa-search">VIEW= {{$album->photos_count}}</span></a>
                                    @else
                                    <span class="fa fa-search"></span>

                                @endif
                                <a title="update album" href="{{route('album.edit', $album->id)}}" class="btn btn-primary">
                                    <span class="fa fa-pencil">UPDATE</span></a>
                                <form id ="form{{$album->id}}" method="POST" action ="{{route('album.delete',$album->id)}}">     
                                    <input type="hidden" name="_token" id="_token" value="{{ csrf_token() }}"> 
                                    <input type="hidden" name="_method" value="DELETE"> 
                                    <button id ="{{$album->id}}" class="btn btn-sm btn-danger">DELETE<span class ="fa fa-minus"></span></button>
                                </form> 
                        </div>
                    </td>
                </tr>
                @endforeach
                </table>
                <tr>
                    <td class="row" colspan="5">

                        <div class="col-md-8 push-2">
    
                            {{$albums->links('vendor.pagination.bootstrap-4')}}
    
                        </div>
                    </td>
                </tr>    
@endsection
@section('footer')
    @parent
    <script>
        $('document').ready(function(){

            $('div.alert').fadeOut(5000);

            $('table-striped').on('click','button.btn-danger',function(element){

                element.preventDefault(); 
                
                var id = element.target.id;
               // var form = $('#form'+ id);
                var urlAlbum = form.attr('action');
                var tr = $('#tr'+ id);
                /*alert(urlAlbum);*/
                $.ajax(urlAlbum,
                    {
                      /*url: urlAlbum,*/ 
                      method:'DELETE',
                      data:{
                          '_token': $('#_token').val()  
                      },
                      complete : function(resp){ 
                          //console.log(resp);
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
