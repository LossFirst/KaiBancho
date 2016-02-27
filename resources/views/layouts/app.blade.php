<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>AdminLTE 2 | Dashboard</title>
    <!-- Tell the browser to be responsive to screen width --> 
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.5 -->
    {!! Html::style("bootstrap/css/bootstrap.min.css",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}
    <!-- Font Awesome -->
    {!! Html::style("https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css") !!}
    <!-- Ionicons -->
    {!! Html::style("https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css") !!}
    <!-- Theme style -->
    {!! Html::style("dist/css/AdminLTE.min.css",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}
    {!! Html::style("dist/css/custom.css",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}
    <!-- AdminLTE Skins. Choose a skin from the css/skins
        folder instead of downloading all of them to reduce the load. -->
    {!! Html::style("dist/css/skins/skin-purple-light.css",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}

   <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
   <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
   <!--[if lt IE 9]>
   {!! Html::script("https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}
   {!! Html::script("https://oss.maxcdn.com/respond/1.4.2/respond.min.js",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}
   <![endif]-->
</head>
<body class="hold-transition skin-purple-light layout-top-nav">
    <div class="wrapper">
       <!-- Nav Content -->
       @include('layouts.nav')
       <!-- Sidebar Content -->
       <!-- Main Content -->
        <div class="content-wrapper">
            <div class="container">
            <!-- Content Header (Page header) -->
                @yield('content')
            </div>
        </div>
        <footer class="main-footer">
            <div class="container">
                <div class="pull-right hidden-xs">
                    <b>Version</b> 2.3.2
                </div>
                <strong>Copyright &copy; 2014-2015 <a href="http://almsaeedstudio.com">Almsaeed Studio</a>.</strong> All rights
                reserved.
            </div>
            <!-- /.container -->
        </footer>
    </div>
    <!-- ./wrapper -->
    <!-- jQuery 2.2.0 -->
    {!! Html::script("plugins/jQuery/jQuery-2.2.0.min.js",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}
    <!-- Bootstrap 3.3.5 -->
    {!! Html::script("bootstrap/js/bootstrap.min.js",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}
    <!-- SlimScroll -->
    {!! Html::script("plugins/slimScroll/jquery.slimscroll.min.js",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}
    <!-- FastClick -->
    {!! Html::script("plugins/fastclick/fastclick.js",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}
    <!-- AdminLTE App -->
    {!! Html::script("dist/js/app.min.js",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}
    <!-- AdminLTE for demo purposes -->
    {!! Html::script("dist/js/demo.js",[], (Request::server('HTTP_X_FORWARDED_PROTO') == 'https')?true:false) !!}
</body>
</html>
