<!doctype html>
<br>
<br>
<br>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
    <meta name="generator" content="Jekyll v3.8.5">

    <title>@yield('title','Home')</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.1/css/lightbox.css" rel="stylesheet" />
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css" />

    <style>
      body {
        background-color:#F0FFFF;
      }
      .bd-placeholder-img {
        font-size: 1.125rem;
        text-anchor: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
      }

      @media (min-width: 768px) {
        .bd-placeholder-img-lg {
          font-size: 3.5rem;
        }
      }
      
      /*FOOTER*/
      

      footer {
        background: #16222A;
        background: -webkit-linear-gradient(59deg, #3A6073, #16222A);
        background: linear-gradient(59deg, #3A6073, #16222A);
        color: white;
        margin-top:100px;
      }

      footer a {
        color: #fff;
        font-size: 14px;
        transition-duration: 0.2s;
      }

      footer a:hover {
        color: #FA944B;
        text-decoration: none;
      }

      .copy {
        font-size: 12px;
        padding: 10px;
        border-top: 1px solid #FFFFFF;
      }

      .footer-middle {
        padding-top: 2em;
        color: white;
      }


      /*SOCİAL İCONS*/

      /* footer social icons */

      ul.social-network {
        list-style: none;
        display: inline;
        margin-left: 0 !important;
        padding: 0;
      }

      ul.social-network li {
        display: inline;
        margin: 0 5px;
      }


      /* footer social icons */

      .social-network a.icoFacebook:hover {
        background-color: #3B5998;
      }

      .social-network a.icoLinkedin:hover {
        background-color: #007bb7;
      }

      .social-network a.icoFacebook:hover i,
      .social-network a.icoLinkedin:hover i {
        color: #fff;
      }

      .social-network a.socialIcon:hover,
      .socialHoverClass {
        color: #44BCDD;
      }

      .social-circle li a {
        display: inline-block;
        position: relative;
        margin: 0 auto 0 auto;
        -moz-border-radius: 50%;
        -webkit-border-radius: 50%;
        border-radius: 50%;
        text-align: center;
        width: 30px;
        height: 30px;
        font-size: 15px;
      }

      .social-circle li i {
        margin: 0;
        line-height: 30px;
        text-align: center;
      }

      .social-circle li a:hover i,
      .triggeredHover {
        -moz-transform: rotate(360deg);
        -webkit-transform: rotate(360deg);
        -ms--transform: rotate(360deg);
        transform: rotate(360deg);
        -webkit-transition: all 0.2s;
        -moz-transition: all 0.2s;
        -o-transition: all 0.2s;
        -ms-transition: all 0.2s;
        transition: all 0.2s;
      }

      .social-circle i {
        color: #595959;
        -webkit-transition: all 0.8s;
        -moz-transition: all 0.8s;
        -o-transition: all 0.8s;
        -ms-transition: all 0.8s;
        transition: all 0.8s;
      }

      .social-network a {
        background-color: #F9F9F9;
      }
      
    </style>
  </head>
  <body>
      <nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
      <a class="navbar-brand" href="#">IMG GALLERY</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsExampleDefault" aria-controls="navbarsExampleDefault" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarsExampleDefault">
        <ul class="navbar-nav mr-auto">
          @if(Auth::check()) 
              <li class="nav-item active">
                <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="/albums">Albums</a>
              </li>
              <li class="nav-item">
                  <a class="nav-link" href="{{route('album.create')}}">New Albums</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="{{route('photos.create')}}">New Image</a>
              </li>
            <li class="nav-item">
              <a class="nav-link" href="{{route('categories.index')}}">Categories</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="{{route('albums.about')}}">About ME:)</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href ="{{route('user.edit',Auth::user()->id)}}"> Modify User</a>
            </li>
            <li>
              <a class="nav-link" href="{{route('album.show.albumimage')}}">Look your Albums</a>
            </li>
        </ul>
          <form class="form-inline my-2 my-lg-0">
            <input class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search">
            <button class="btn btn-secondary my-2 my-sm-0" type="submit">Search</button>
          </form>
        @endif
        <ul class="nav navbar-nav navbar-right">
          <!-- Authentication Links -->
          @if (Auth::guest())
              <li><a class="nav-link" href="{{ route('login') }}">Login</a></li>
              <li><a class="nav-link" href="{{ route('register') }}">Register</a></li>
          @else
              <li class="dropdown">
                  <a href="#" class="dropdown-toggle nav-link" data-toggle="dropdown" role="button" aria-expanded="false">
                      {{ Auth::user()->name }} <span class="caret"></span>
                  </a>
                  <ul class="dropdown-menu" role="menu">
                      <li>
                          <a href="{{ route('logout') }}"
                              onclick="event.preventDefault();
                                       document.getElementById('logout-form').submit();">
                              Logout
                          </a>
                          <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                              {{ csrf_field() }}
                          </form>
                      </li>
                  </ul>
              </li>
          @endif
        </ul>
      </div>
    </nav>

    <div class="container">
      @yield('content')

    </div>


    @section('footer')

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.1/js/lightbox.min.js"></script>
    @show

    <footer class="mainfooter" role="contentinfo">
      <div class="footer-middle">
      <div class="container">
        <div class="row">
          <div class="col-md-2 col-lg-3 col-xl-3 mx-auto mt-3">
            <h6 class="text-uppercase mb-4 font-weight-bold">Contact ME</h6>
            <p>
              <i class="fa fa-home mr-3"></i> Legnano, MI 20025, MI</p>
            <p>
              <i class="fa fa-envelope mr-3"></i> lucaairoldi93@gmail.com</p>
            <p>
              <i class="fa fa-phone mr-3"></i> + 39 3480150047</p>
          </div>
        </div>
        <div class="row">
            <div class="col-md-12 copy">
                <p class="text-center">&copy; Copyright 2019 - .This Site is created by LUCA AIROLDI  All rights reserved.</p>
            </div>
        </div>
    
    
      </div>
      </div>
    </footer>
  </body>
</html>














