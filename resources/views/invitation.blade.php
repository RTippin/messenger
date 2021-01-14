@extends('messenger::app')
@section('title')
    Join Group
@endsection
@push('css')
    <style>
        body {
            background: #3d9a9b;
        }
    </style>
    <meta name="robots" content="noindex">
@endpush
@section('content')
    <div class="container">
        <div class="jumbotron bg-gradient-dark text-white">
            <div id="inv_loading" class="text-center">
                <h2>Join with Invite <div class="spinner-grow text-primary" role="status"></div></h2>
            </div>
            <div id="inv_loaded"></div>
        </div>
    </div>
    <div id="inv_actions_ctnr" class="col-12 text-center mt-5 NS"></div>
    @guest
    {{--Your login form--}}
    @endguest
@endsection
@push('Messenger-modules')
    InviteJoin : {
    src : 'InviteJoin.js',
    code : '{{$code}}'
    },
@endpush

