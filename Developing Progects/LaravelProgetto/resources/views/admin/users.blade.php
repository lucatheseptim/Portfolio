@extends('templates.admin')
@section('title','Users')
@section('content')
@section('footer')
    @parent
   <h1>Users</h1>
   <table class="table table-striped" id="myTable">
       <thead>
           <tr>
                <th>NAME</th>
                <th>EMAIL</th>
                <th>ROLE</th>
                <th>CREATED AT</th>
                <th>DELETED AT</th>
           <tr>
        
       </thead>
       <tbody>
           @foreach($users as $user)
           <tr>
               <td>{{$user->name}} </td>
               <td>{{$user->email}} </td>
               <td>{{$user->role}} </td>
               <td>{{$user->created_at}} </td>
               <td>{{$user->deleted_at}} </td>
               <td>
                    <div class="row">
                        <div class="col-4">
                            <button class="btn btn-sm btn-primary">UPDATE<span class ="fa fa-pencil"></span></button>
                        </div>
                        <div class="col-4">
                            <button  @if($user->deleted_at)
                                            disabled
                                     @endif
                            class="btn btn-sm btn-danger">DELETE<span class ="fa fa-minus"></span></button>
                        </div>
                        <div class="col-4">
                            <button class="btn btn-sm btn-danger">FORCE DELETE
                                <span class ="fa fa-minus"></span></button>
                        </div>
                    </div>
               </td>
           </tr>
           @endforeach
       </tbody>
   </table>
@endsection
@endsection
