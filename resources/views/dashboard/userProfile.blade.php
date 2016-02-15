@extends('layouts.app')

@section('content')
    <section class="content">
        <div class="col-md-12">
            <!-- Widget: user widget style 1 -->
            <div class="box box-widget widget-user">
                <!-- Add the bg color to the header using any of the bg-* classes -->
                <div class="widget-user-header bg-black" style="background: url('http://new.ppy.sh/images/headers/profile-covers/c1.jpg') center center;">
                    <h3 class="widget-user-username"><strong>{{ $user->name }}</strong></h3>
                    <h5 class="widget-user-desc">joined {{ date('F Y', strtotime($user->created_at)) }}</h5>
                    @if($user->usergroup >= 4)
                        <h5 class="widget-user-desc"><span style="color: #FFCC00; font-weight: bold; text-shadow: 2px 0px 11px #FFCC00;">Supporter</span></h5>
                    @endif
                </div>
                <div class="widget-user-image">
                    @if(Auth::user())
                        @if($user->id == Auth::user()->id)
                            <img class="img-circle" src="{{url("/".$user->id)}}" alt="User Avatar">
                            <a href="{{ url("/dashboard/avatar") }}" class="cornerLink">Edit</a>
                        @else
                            <img class="img-circle" src="{{url("/".$user->id)}}" alt="User Avatar">
                        @endif
                    @else
                        <img class="img-circle" src="{{url("/".$user->id)}}" alt="User Avatar">
                    @endif
                </div>
                <div class="box-footer">

                    <!-- /.row -->
                </div>
            </div>
            <!-- /.widget-user -->
        </div>
        <div class="col-md-12">
            <!-- Widget: user widget style 1 -->
            <div class="box box-widget widget-user">
                <div class="box-footer">
                    <div class="row">
                        <div class="col-sm-4 border-right">
                            <div class="description-block">
                                <h5 class="description-header">{{ $rank }}</h5>
                                <span class="description-text">Global Rank</span>
                            </div>
                            <!-- /.description-block -->
                        </div>
                        <!-- /.col -->
                        <div class="col-sm-4 border-right">
                            <div class="description-block">
                                <h5 class="description-header">{{ number_format($user->OsuUserStats->ranked_score) }}</h5>
                                <span class="description-text">Ranked Score</span>
                            </div>
                            <!-- /.description-block -->
                        </div>
                        <!-- /.col -->
                        <div class="col-sm-4">
                            <div class="description-block">
                                <h5 class="description-header">{{ round($accuracy * 100) }}%</h5>
                                <span class="description-text">Accuracy</span>
                            </div>
                            <!-- /.description-block -->
                        </div>
                        <!-- /.col -->
                    </div>
                    <!-- /.row -->
                </div>
            </div>
            <!-- /.widget-user -->
        </div>
    </section>
@endsection
