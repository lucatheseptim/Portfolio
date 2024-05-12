@extends('templates.layout')
@section('title','Manage Category')
@section('content')
<h1>Manage Categories</h1>
    <div class="row">
        <div class="col-6 push-2">
        @include('categories.categoryform')
        </div>    
    </div>
@endsection