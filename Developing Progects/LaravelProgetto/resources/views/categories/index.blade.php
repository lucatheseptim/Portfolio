@extends('templates.layout')
@section('title','Categories Album')
@section('content')
@include('partials.inputerrors')
<div class="row">
    <div class="col-8">
        <table class="table table-striped">
            <tr>
                <th>ID</th>
                <th>Category name</th>
                <th>Created Date</th>
                <th>Update Date</th>
                <th>Numbres of albums</th>
            </tr>
            @forelse($categories as $category)
                <tr id="tr-{{$category->id}}">
                    <td>{{$category->id}}</td>
                    <td>{{$category->category_name}}</td>
                    <td>{{$category->created_at}}</td>
                    <td>{{$category->updated_at}}</td>
                    <td>{{$category->albums_count}}</td>
                    <td> 
                        <form action="{{route('categories.destroy',$category->id)}}" method="post" >
                            <input type="hidden" name="_token" id="_token" value="{{ csrf_token() }}"> 
                            <input type="hidden" name="_method" value="DELETE">
                            <button id="{{$category->id}}" class="btn btn-sm btn-danger">DELETE<span class ="fa fa-minus"></span></button>
                        </form>
                        <a href="{{route('categories.edit',$category->id)}}"<button id ="" class="btn btn-sm btn-update">UPDATE<span class ="fa fa-pencil"></span></button></a>
                    </td>
                </tr>
                @empty
            @endforelse
        </table>
        <div class="row">
            <div class="col-md-8 push-2">{{$categories->links('vendor.pagination.bootstrap-4')}}</div>
        </div>
    </div>
    <div class="col-4">
        <h2>Add New Category</h2>
        <form id = "manageCategoryForm" action="{{route('categories.store')}}" method="POST">
            {{csrf_field()}}   
            <label for="category_name">Category name</label>
            <input required name="category_name" id="category_name" class="form-control">
            <br>
            <div class="form-group">
                <button class="btn btn-primary"><span class ="fa fa-save"></span>SAVE</button>
            </div>
        </form>
    </div>
</div>
@endsection
@section('footer')
    @parent
    <script>
        $('document').ready(function(){
            
            $('form').on('click','button.btn-danger',function(evt){

                evt.preventDefault(evt);

                console.log(evt.target);

                var form = this.parentNode; //ho il form 

                var urlCategory = form.action; //ho l'url del form

                console.log(urlCategory);

                var categoryId = this.id;

                console.log(categoryId);

                var trId ='tr-'+categoryId;

                console.log(trId);
                $.ajax(urlCategory,
                    {
                        method:'DELETE', /*dichiaro il metodo*/
                        data:{
                            '_token': $('#_token').val()  /*prendo il valore del Token tramite ID*/
                        },
                        complete : function(resp){ /*indipendentemente che la richiesta ajax sia andata a buon fine la chiamo*/
                            console.log(resp);
                            var resp = JSON.parse(resp.responseText);
                            alert(resp.message);
                            $('#'+trId).remove().fadeOut(1000);

                        }
                    }
        
                )
                
            });
            $('#manageCategoryForm .button.btn-primary').on('click',function(evt){

                evt.preventDefault(evt);

                console.log(evt.target);

                var form = $('#manageCategoryForm');

                var dati = form.serialize();  //serializzo tuti i dati 

                var urlCategory = form.attribute('action');

                $.ajax(urlCategory,
                    {
                        method:'POST', /*dichiaro il metodo*/
                        data: dati,
                        complete : function (resp) {

                            var response = JSON.parse(resp.responseText);
                            alert(response.message);
                            if(response.success){

                            } else {
  
                                alert('Problem contacting server');
  
                            }
                        }
                    }  
                )
                
            });
        });//chiuso document.ready
    </script>
@endsection