@extends('templates.layout')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="card">
                <h5 class="card-header info-color white-text text-center py-4">
                    <strong style="color: #0000FF">Register</strong>
                </h5>
                <div class="card-body px-lg-5 pt-0">
                    <form class="form-horizontal" method="POST" action="{{ route('register') }}">
                        {{ csrf_field() }}

                        <div class="md-form{{ $errors->has('name') ? ' has-error' : '' }}">
                            <label for="name">Name</label>
                                <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}" required autofocus>

                                @if ($errors->has('name'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('name') }}</strong>
                                    </span>
                                @endif
                        </div>
                       
                        <div class="md-form{{ $errors->has('email') ? ' has-error' : '' }}">
                            <label for="email">E-Mail Address</label>
                                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}" required>

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

                        <div class="md-form">
                            <label for="password-confirm">Confirm Password</label>
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
                        </div>

                        Role:
                        <div class="md-form">
                            <select class="form-group" name="role" id="role" multiple size ="1">
                                <option value = "admin">admin</option>
                                <option value = "user">user</option>
                            </select>
                        </div>

                        <div class="md-form">
                                <button type="submit" class="btn btn-outline-info btn-rounded btn-block my-4 waves-effect z-depth-0">
                                    Register
                                </button>     
                        </div>
                        <div class="md-form">
                            <h5> *After registration 
                                check your email </h5>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
</div>
@endsection
