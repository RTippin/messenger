<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#343a40"/>
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
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">
    <link href="https://cdn.datatables.net/1.10.19/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    @stack('css')
</head>
<body>
<wrapper class="d-flex flex-column">
    <main class="flex-fill">
        <div id="app">
            @yield('content')
        </div>
    </main>
</wrapper>
@include('messenger::scripts')
</body>
</html>