@extends('templates.layout')

@section('content')
<h1>
    {{$title}}  
</h1>
    
    @if($staff)
        <ul>
        @foreach($staff as $person)
            <li>{{$person['name']}}</li>
        @endforeach
        <ul>
    @endif   
@endsection('content')

/*in questo modo ho il javascript nella pagina staff.blade.php*/
@section('footer')
    @parent
        <script>
            alert('footer')
        </script>    
    @stop

