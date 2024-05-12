@extends('templates.layout')
@section('content')
@if(session()->has('UserDelete')) 
    <div class="alert alert-info">{{session()->get('UserDelete')}}</div>
@endif
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">   
            <div class="card">
                <h5 class="card-header info-color white-text text-center py-4">
                    <strong style="color: #0000FF">Sign in</strong>
                </h5>
                <div class="card-body px-lg-5 pt-0">
                   
                    <form class="text-center" style="color: #757575;" method="POST" action="{{ route('login') }}">
                        {{ csrf_field() }}

                            <div class="md-form{{ $errors->has('email') ? ' has-error' : '' }}">
                                <label for="email">E-Mail Address</label>
                                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required autofocus>
                                    @if ($errors->has('email'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('email') }}</strong>
                                        </span>
                                    @endif
                            </div>
                            <div class="md-form{{ $errors->has('password') ? ' has-error' : '' }}">
                                <label for="password">Password</label>
                                <input id="password" type="password" class="form-control" name="password" required>
                                    @if ($errors->has('password'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('password') }}</strong>
                                        </span>
                                    @endif
                            </div>
                            <div class="d-flex justify-content-around">
                                <div class="md-form">
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Remember Me
                                            </label>
                                        </div>
                                </div>
                                        <a class="btn btn-link" href="{{ route('password.request') }}">
                                            Forgot Your Password?
                                        </a>
                            </div>
                            <div class="md-form">
                                <button type="submit" class="btn btn-outline-info btn-rounded btn-block my-4 waves-effect z-depth-0">
                                    Login
                                </button>
                            </div>
                    </form>  
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
