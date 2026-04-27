<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Products')</title>

    <!-- CSS Files -->
    <link rel="stylesheet" type="text/css" href="{{ url('asset/css/bootstrap.min.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ url('asset/css/style.css') }}">
    <link rel="stylesheet" href="{{ url('asset/css/responsive.css') }}">
    <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
    <link rel="stylesheet" href="{{ url('asset/datatable/jquery.dataTables.min.css') }}">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ url('asset/datatable/jquery.dataTables.min.js') }}"></script>


</head>
<body>

    <!-- Header -->
   <!-- Header -->
@if(Auth::check())
    @include('partials.header')
@endif
    <!-- Main Content -->
    <div class="container" style="margin-right: none !important;">
        @yield('content')
    </div>

    <!-- Footer -->
    <!-- Header -->
@if(Auth::check())
    @include('partials.footer')
@endif

    <!-- JS Files -->

    <!-- jQuery Validation Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.js"></script>
    <script src="{{ url('asset/js/bootstrap.bundle.min.js') }}"></script>
  <script type="text/javascript">
      
       function openNav() {
     document.getElementById("mySidenav").style.width = "100%";
   }
   
   function closeNav() {
     document.getElementById("mySidenav").style.width = "0";
   }
  </script>



</body>
</html>
