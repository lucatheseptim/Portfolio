

<!-- Navigation-->

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top" id="mainNav">

    <a class="navbar-brand" href="index.html">LARAGALLERY ADMIN DASHBOARD</a>

    <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">

        <span class="navbar-toggler-icon"></span>

    </button>

    <div class="navbar-collapse" id="navbarResponsive">

        <ul class="navbar-nav navbar-sidenav" id="exampleAccordion">

            <li class="nav-item" data-toggle="tooltip" data-placement="right" title="Dashboard">

                <a class="nav-link" href="/">

                    <i class="fa fa-fw fa-dashboard"></i>

                    <span class="nav-link-text">ADMIN</span>

                </a>

            </li>

            <li class="nav-item" data-toggle="tooltip" data-placement="right" title="Users">

                <a class="nav-link nav-link-collapse collapsed" data-toggle="collapse" href="#collapseUsers" data-parent="#collapseUsers">



                <i class="fa fa-fw fa-wrench"></i>

                    <span class="nav-link-text">Users</span>

                </a>

                <ul class="sidenav-second-level collapse" id="collapseUsers">

                    <li>

                        <a href="{{route('user-list')}}">User list</a>

                    </li>

                    <li>

                        <a href="">New user</a>

                    </li>

                </ul>

            </li>



           

            <li class="nav-item" data-toggle="tooltip" data-placement="right" title="Tables">

                <a class="nav-link" href="tables.html">

                    <i class="fa fa-fw fa-file-image-o"></i>

                    <span class="nav-link-text">Albums Categories</span>

                </a>

            </li>

   

            <li class="nav-item" data-toggle="tooltip" data-placement="right" title="Link">

                <a class="nav-link" href="#">

                    <i class="fa fa-fw fa-book"></i>

                    <span class="nav-link-text">Albums</span>

                </a>

            </li>

            <li class="nav-item" data-toggle="tooltip" data-placement="right" title="Link">

                <a class="nav-link" href="#">

                    <i class="fa fa-fw fa-picture-o"></i>

                    <span class="nav-link-text">Pictures</span>

                </a>

            </li>

        </ul>

        <ul class="navbar-nav sidenav-toggler">

            <li class="nav-item">

                <a class="nav-link text-center" id="sidenavToggler">

                    <i class="fa fa-fw fa-angle-left"></i>

                </a>

            </li>

        </ul>

        <ul class="navbar-nav navbar-left mr-auto">

            <li class="nav-item">

                <a class="nav-link text-left">

                    <i class="fa fa-fw fa-home">HOME</i>

                </a>

            </li>

          

        </ul>

        <ul class="navbar-nav navbar-right ml-auto">

           

            <li class="nav-item">

                <form id="logout-form" action="{{ url('/logout') }}" method="POST" style="display: none;">

                    {{ csrf_field() }}

                </form>

                <a class="nav-link"  onclick="event.preventDefault();

                                                     document.getElementById('logout-form').submit();" href="{{route('logout')}}">

                    <i class="fa fa-fw fa-sign-out"></i>Logout</a>

            </li>

        </ul>

    </div>

</nav>

<div class="content-wrapper">

    <div class="container-fluid">

        <!-- Breadcrumbs-->

        <ol class="breadcrumb">

            <li class="breadcrumb-item">

                <a href="/">Admin Dashboard</a>

            </li>

            <li class="breadcrumb-item active">{{Route::currentRouteName()}}</li>

        </ol>

        <div class="row">

            <div class="col-12">

              @yield('content')  

            </div>

        </div>

    </div>

    <!-- /.container-fluid-->

    <!-- /.content-wrapper-->


    <!-- Scroll to Top Button-->

    <a class="scroll-to-top rounded" href="#page-top">

        <i class="fa fa-angle-up"></i>

    </a>



    @section('footer')

    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" style="stylesheet"/>
    <link href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap.min.css" style="stylesheet"/>
    <script src="https://code.jquery.com/jquery-3.3.1.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap.min.js"></script>
    <script>
        $(document).ready( function () {
        $('#myTable').DataTable();
    } );
    </script>
    
    @show

</div>



