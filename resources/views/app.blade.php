<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="application-name" content="FS">
    <meta name="apple-mobile-web-app-title" content="FS">
    <meta name="theme-color" content="#343a40">
    <meta name="msapplication-navbutton-color" content="#343a40">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="msapplication-starturl" content="/">
    <link rel="icon" type="image/png" sizes="192x192" href="{{asset('vendor/messenger/images/android-chrome-192x192.png')}}">
    <link rel="apple-touch-icon" type="image/png" sizes="180x180" href="{{asset('vendor/messenger/images/apple-touch-icon.png')}}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{asset('vendor/messenger/images/favicon-32x32.png')}}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{asset('vendor/messenger/images/favicon-16x16.png')}}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="title" content="@yield('title', messenger()->getSiteName())">
    <title>@yield('title', messenger()->getSiteName())</title>
    @auth
        <link id="main_css" href="{{ asset(mix(messenger()->getProviderMessenger()->dark_mode ? 'dark.css' : 'app.css', 'vendor/messenger')) }}" rel="stylesheet">
    @else
        <link id="main_css" href="{{ asset(mix('dark.css', 'vendor/messenger')) }}" rel="stylesheet">
    @endauth
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.7.1/css/all.min.css">
    <link href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    @stack('css')
</head>
<body>
<wrapper class="d-flex flex-column">
    <nav id="FS_navbar" class="navbar fixed-top navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand" href="{{url('/')}}">
            <img src="{{ asset('vendor/messenger/images/messenger.png') }}" width="30" height="30" class="d-inline-block align-top" alt="Messenger">
            {{messenger()->getSiteName()}}
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
            <span class="badge badge-pill badge-danger mr-n2" id="nav_mobile_total_count"></span>
        </button>
        <div id="navbarNavDropdown" class="navbar-collapse collapse">
            @auth
                @include('messenger::nav')
            @endauth
        </div>
    </nav>
    <main id="FS_main_section" class="pt-5 mt-4 flex-fill">
        <div id="app">
            @yield('content')
        </div>
    </main>
</wrapper>
@include('messenger::scripts')
</body>
</html>