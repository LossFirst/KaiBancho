<header class="main-header">
    <nav class="navbar navbar-static-top">
        <div class="container">
            <div class="navbar-header">
                <a href="{{ url("/") }}" class="navbar-brand"><b>Kai</b>Bancho</a>
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
                    <i class="fa fa-bars"></i>
                </button>
            </div>

            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse pull-left" id="navbar-collapse">
                <ul class="nav navbar-nav">
                    <li class="active"><a href="#">Link <span class="sr-only">(current)</span></a></li>
                    <li><a href="#">Link</a></li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Dropdown <span class="caret"></span></a>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="#">Action</a></li>
                            <li><a href="#">Another action</a></li>
                            <li><a href="#">Something else here</a></li>
                            <li class="divider"></li>
                            <li><a href="#">Separated link</a></li>
                            <li class="divider"></li>
                            <li><a href="#">One more separated link</a></li>
                        </ul>
                    </li>
                </ul>
                <form class="navbar-form navbar-left" role="search">
                    <div class="form-group">
                        <input type="text" class="form-control" id="navbar-search-input" placeholder="Search">
                    </div>
                </form>
            </div>
            <!-- /.navbar-collapse -->
            <!-- Navbar Right Menu -->
            <div class="navbar-custom-menu">
                <ul class="nav navbar-nav">
                    @if(auth()->guest())
                        <li class="dropdown user user-menu">
                            <!-- Menu Toggle Button -->
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <span>Login</span>
                            </a>
                            <ul class="dropdown-menu">
                                <!-- Menu Body -->
                                <li class="user-body">
                                    <form class="form-horizontal" role="form" method="POST" action="{{ secure_url('/login') }}">
                                        {!! csrf_field() !!}
                                        <div class="form-group{{ $errors->has('email') ? ' has-error has-feedback' : '' }}">
                                            <div class="col-sm-10 col-sm-offset-1">
                                                <div class="input-group">
                                                    <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                                                    <input type="email" class="form-control" name="email" placeholder="E-Mail" value="{{ old('email') }}"{{ $errors->has('email') ? 'aria-describedby="emailErrorStatus"' : '' }}>
                                                </div>
                                                @if ($errors->has('email'))
                                                    <span class="help-block"><strong>{{ $errors->first('email') }}</strong></span>
                                                    <span class="glyphicon glyphicon-remove form-control-feedback" aria-hidden="true"></span>
                                                    <span id="emailErrorStatus" class="sr-only">(error)</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group{{ $errors->has('password') ? ' has-error has-feedback' : '' }}">
                                            <div class="col-sm-10 col-sm-offset-1">
                                                <div class="input-group">
                                                    <span class="input-group-addon"><i class="fa fa-key"></i></span>
                                                    <input type="password" class="form-control" name="password" placeholder="Password"{{ $errors->has('password') ? 'aria-describedby="passwordErrorStatus"' : '' }}>
                                                </div>
                                                @if ($errors->has('password'))
                                                    <span class="help-block"><strong>{{ $errors->first('password') }}</strong></span>
                                                    <span class="glyphicon glyphicon-remove form-control-feedback" aria-hidden="true"></span>
                                                    <span id="passwordErrorStatus" class="sr-only">(error)</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-offset-1 col-sm-10 col-sm-offset-1">
                                                <div class="checkbox">
                                                    <label>
                                                        <input type="checkbox" name="remember"> Remember me
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <div class="col-sm-offset-1 col-sm-12">
                                                <button type="submit" class="btn btn-default">Sign in</button>
                                                <a class="btn btn-link" href="{{ secure_url('/password/reset') }}">Forgot Your Password?</a>
                                            </div>
                                        </div>
                                    </form>
                                </li>
                            </ul>
                        </li>
                        <li><a href="{{ secure_url('/register') }}">Register</a></li>
                    @else
                        <!-- User Account Menu -->
                        <li class="dropdown user user-menu">
                            <!-- Menu Toggle Button -->
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <!-- The user image in the navbar-->
                                <img src="{{ url("/".Auth::user()->id) }}" class="user-image" alt="User Image">
                                <!-- hidden-xs hides the username on small devices so only the image appears. -->
                                <span class="hidden-xs">{{ auth()->user()->name }}</span>
                            </a>
                            <ul class="dropdown-menu">
                                <!-- The user image in the menu -->
                                <li class="user-header" style="background: url('http://new.ppy.sh/images/headers/profile-covers/c1.jpg'); background-size: cover; background-position: center;">
                                    <img src="{{ url("/".Auth::user()->id) }}" class="img-circle" alt="User Image">

                                    <p>
                                        {{ auth()->user()->name }}
                                        <small>Member since {{ date('F Y', strtotime($user->created_at)) }}</small>
                                    </p>
                                </li>
                                <!-- Menu Footer-->
                                <li class="user-footer">
                                    <div class="pull-left">
                                        <a href="/u/{{ Auth::user()->id }}" class="btn btn-default btn-flat">Profile</a>
                                    </div>
                                    <div class="pull-right">
                                        <a href="{{ secure_url('/logout') }}" class="btn btn-default btn-flat">Sign out</a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    @endif
                </ul>
            </div>
            <!-- /.navbar-custom-menu -->
        </div>
        <!-- /.container-fluid -->
    </nav>
</header>
