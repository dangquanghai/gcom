<?php  $head = explode('.', request()->route()->getname());?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  
  <title>GCOM | Dashboard</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
  <!-- Font Awesome -->

 <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bbootstrap 4 -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
  <link rel="stylesheet" href= "https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
  <!-- Include the Bootstrap Table CSS for the table -->
  <!-- Include jQuery and other required  files for Bootstrap -->
  <script src="https://code.jquery.com/jquery-3.3.1.min.js">  </script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js">  </script>
  <script src= "https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js">   </script>
  <!-- Include the JavaScript file  for Bootstrap table -->

<link href="https://unpkg.com/bootstrap-table@1.18.3/dist/bootstrap-table.min.css" rel="stylesheet">
<link href="https://unpkg.com/bootstrap-table@1.18.3/dist/extensions/fixed-columns/bootstrap-table-fixed-columns.min.css" rel="stylesheet">

<script src="https://unpkg.com/bootstrap-table@1.18.3/dist/bootstrap-table.min.js"></script>
<script src="https://unpkg.com/bootstrap-table@1.18.3/dist/extensions/fixed-columns/bootstrap-table-fixed-columns.min.js"></script>

  <script src="https://unpkg.com/tableexport.jquery.plugin/tableExport.min.js"></script>
  <script src="https://unpkg.com/tableexport.jquery.plugin/libs/jsPDF/jspdf.min.js"></script>
  <script src="https://unpkg.com/tableexport.jquery.plugin/libs/jsPDF-AutoTable/jspdf.plugin.autotable.js"></script>
  <script src="https://unpkg.com/bootstrap-table@1.18.0/dist/extensions/export/bootstrap-table-export.min.js"></script>
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.10.24/datatables.min.css"/>
  <script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.24/datatables.min.js"></script>
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="sidebar-mini layout-fixed sidebar-collapse" style="height: auto;">
<div class="wrapper">
  <!-- Navbar -->

  <!-- /.navbar -->
  <!-- Main Sidebar Container -->
 
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- container-fluid -->
<!-- / Main Sidebar Container -->
<div class="scroll-content">
      @yield('content')
</div>
  </div>
  <!-- /.content-wrapper -->
  <!-- Control Sidebar -->
 
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->
<!-- jQuery UI 1.11.4 -->
<!-- @include('layouts.footer')  -->
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->

</body>
</html>
