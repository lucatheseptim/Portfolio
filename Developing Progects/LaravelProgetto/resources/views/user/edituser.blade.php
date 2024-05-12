@extends('templates.layout')
@section('title','Edit User')
@section('content')
<div class="col-md-8 col-md-offset-2"> 
    <div class="card">
        <h5 class="card-header info-color white-text text-center py-4">
            <strong style="color: #0000FF">Edit User</strong>
        </h5>
        <form action ="{{route('user.update',$user->id)}}" method="POST">
            {{ csrf_field() }} 
            {{method_field('PATCH')}} 
            <div class="form-group">
                <label for="Name">Name</label>
                <input type ="text"  name ="name" id="name" class="form-control" value ={{old('name',$user->name)}}>
            </div>
            <div class="form-group">
                <label for="newpassword"> New Password</label>
                <input type ="text"  name ="newpassword" id ="newpassword" class="form-control">
            </div>
            <div class="form-group">
                <label for="confirmpassword"> Confirm Password</label>
                <input type ="text"  name ="confirmpassword" id ="confirmpassword" class="form-control">
            </div>
            <div class="form-group">
                <label for="email">Mail</label>
                <input type="text"  name ="email" id ="email" class="form-control" value={{old('email',$user->email)}}>
            </div>
            <div class="form-group">
                Role:
                <select class="form-control" name="role" id="role">
                    @if($user->role === "user")
                    <option value = {{$user->id}} 'selected'>{{$user->role}}</option>
                    <option value = {{$user->id}} >admin</option>
                        @else
                        <option value = {{$user->id}} 'selected'>{{$user->role}}</option>
                        <option value = {{$user->id}}>user</option>
                    @endif
                </select>
            </div>
            <button type="submit" class="btn btn-outline-info btn-rounded btn-block my-4 waves-effect z-depth-0">Submit</button>
        </form>
        <form action ="{{route('user.destroy',$user->id)}}" method="POST">
            {{csrf_field()}}
            {{method_field('DELETE')}} 
            <button class="btn btn-danger btn-lg btn-block">DELETE<span class ="fa fa-minus"></span></button>
        </form>
    </div>
</div>
@endsection
