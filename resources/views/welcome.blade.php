@extends('layouts.app')

@section('content')
<section class="content">
    @if(config('bancho.maintenanceMode'))
    <div class="callout callout-danger">
        <h4>Server Maintenance!</h4>

        <p>The server is currently under maintenance, you won't be able to get on during this time.</p>
    </div>
    @endif
    <div class="box box-default">
        <div class="box-header with-border">
            <h3 class="box-title">Blank Box</h3>
        </div>
        <div class="box-body">
            The great content goes here
        </div>
        <!-- /.box-body -->
    </div>
    <!-- /.box -->
</section>
@endsection
