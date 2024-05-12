@extends('templates.layout')
@section('title','Sending Email')
@section('content')

<h2>this site was built by LUCA AIROLDI {{$user['name']}}  </h2>
<h2>Welcome to the sites {{$user['name']}}  </h2>
<br/>
Your registered email-id is {{$user['email']}}
