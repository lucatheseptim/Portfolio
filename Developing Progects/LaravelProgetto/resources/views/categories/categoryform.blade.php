<div class="row">
    <div class="col-md-10">
        <form id = "manageCategoryForm" action="{{($category->category_name) ? route('categories.store') : route('categories.update',$category->id)}}" method="POST" class = "form-inline">
            {{csrf_field()}}   
            {{($category->category_name) ? method_field('PATCH') : '' }} 
            <label for="category_name">Category name</label>
            <input required name="category_name" id="category_name" value ="{{$category->category_name}}"class="form-control">
            <br>
            <div class="form-group">
                <button class="btn btn-primary"><span class ="fa fa-save"></span>SAVE</button>
            </div>
        </form>
    </div>
    <div class="col-md-2">
        @if($category->category_name)
            <form action="{{route('categories.destroy',$category->id)}}" method="post" class = "form-inline" >
                {{method_field('DELETE')}} 
                {{ csrf_field() }}  
                <button id ="" class="btn btn-danger">DELETE<span class ="fa fa-minus"></span></button>
            </form>
        @endif
    </div>
</div>