@extends('messenger::popup')
@section('title')Video Call @endsection
@section('content')
    <div id="videos" class="container-fluid h-100 NS">
        <div class="mt-2 row" id="other_videos_ctrn"></div>
        <div id="empty_room" class="mt-2 col-12 px-0">
            <div class="col-12 col-sm-4 bg-gradient-dark shadow-lg rounded mx-auto pt-3">
                <div class="col-12 text-center flip-loader-container">
                    <h5 class="text-light text-center">Waiting for others to join</h5>
                    <div class="flip-loader"><div></div><div></div><div></div></div>
                </div>
            </div>
        </div>
        <div class="mine_video_call NS" id="my_video_ctrn"></div>
    </div>
    <div id="hang_up" class="fixed-bottom NS">
        <nav id="FS_navbar" class="navbar fixed-bottom navbar-expand navbar-dark bg-dark">
            <div class="navbar-collapse collapse justify-content-start">
                <ul id="video_main_nav" class="navbar-nav">
                    <li class="nav-item mr-2 dropup">
                        <button data-tooltip="tooltip" title="Streaming Options" data-placement="left" class="btn text-secondary btn-light pt-1 pb-0 px-2 dropdown-toggle" data-toggle="dropdown"><i class="fas fa-cog fa-2x"></i></button>
                        <div class="dropdown-menu dropdown-menu-left">
                            <span id="rtc_options_dropdown"></span>
                        </div>
                    </li>
                    <li id="end_call_nav" class="nav-item mr-2 NS">
                        <button data-toggle="tooltip" title="End Call" data-placement="top" id="end_call_btn" onclick="JanusServer.hangUp(true)" class="btn btn-warning pt-1 pb-0 px-2"><i class="fas fa-times-circle fa-2x"></i></button>
                    </li>
                    <li class="nav-item mr-2">
                        <button data-toggle="tooltip" title="Leave Call" data-placement="top" id="hang_up_btn" onclick="JanusServer.hangUp(false)" class="btn btn-danger pt-1 pb-0 px-2"><i class="fas fa-phone-slash fa-2x"></i></button>
                    </li>
                </ul>
            </div>
            <ul id="video_main_nav2" class="navbar-nav justify-content-end">
                <li class="nav-item mr-2 rtc_nav_opt rtc_nav_video NS">
                    <button onclick="JanusServer.toggleVideo()" data-toggle="tooltip" title="Disable video" data-placement="top" class="btn btn-outline-success pt-1 pb-0 px-2 rtc_video_on rtc_nav_opt NS"><i class="fas fa-video fa-2x"></i></button>
                    <button onclick="JanusServer.toggleVideo()" data-toggle="tooltip" title="Enable video" data-placement="top" class="btn btn-outline-danger pt-1 pb-0 px-2 rtc_video_off rtc_nav_opt NS"><i class="fas fa-video-slash fa-2x"></i></button>
                </li>
                <li class="nav-item mr-2 rtc_nav_opt rtc_nav_audio NS">
                    <button onclick="JanusServer.toggleMute()" data-tooltip="tooltip" title="Mute mic" data-placement="top" class="btn btn-outline-success pt-1 pb-0 px-3 rtc_audio_on rtc_nav_opt NS"><i class="fas fa-microphone fa-2x"></i></button>
                    <button onclick="JanusServer.toggleMute()" data-tooltip="tooltip" title="Unmute mic" data-placement="top" class="btn btn-outline-danger pt-1 pb-0 px-2 rtc_audio_off rtc_nav_opt NS"><i class="fas fa-microphone-slash fa-2x"></i></button>
                </li>
                <li class="nav-item mr-2 rtc_nav_opt rtc_nav_screen NS">
                    <button onclick="JanusServer.toggleScreenShare()" data-tooltip="tooltip" title="Share screen" data-placement="top" class="btn btn-outline-info pt-1 pb-0 px-2 rtc_screen_off rtc_nav_opt NS"><i class="fas fa-desktop fa-2x"></i></button>
                    <button onclick="JanusServer.toggleScreenShare()" data-tooltip="tooltip" title="Stop screen share" data-placement="top" class="btn btn-outline-success pt-1 pb-0 px-2 rtc_screen_on rtc_nav_opt glowing_warning_btn NS"><i class="fas fa-desktop fa-2x"></i></button>
                </li>
            </ul>
        </nav>
    </div>
@stop

@push('js')
<script src="{{asset(mix('JanusServer.js', 'vendor/messenger'))}}"></script>
@endpush

    @push('Messenger-load')
            JanusServer : {src : 'JanusServer.js'},
    @endpush
@push('Messenger-call')
    call : {
        call_id : '{{$callId}}',
        thread_id : '{{$threadId}}',
        janus_secret : '{{config('janus.api_secret')}}',
        janus_debug : {{config('janus.client_debug') ? 'true' : 'false'}},
        janus_ice : @json(config('janus.ice_servers')),
        janus_main : @json(config('janus.main_servers'))
    },
@endpush
@push('css')
    <style>
        body {
            background: #3d9a9b;
        }
    </style>
@endpush