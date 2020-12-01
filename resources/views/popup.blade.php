<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="google-site-verification" content="pTIrzoZHCHvXia5rWOGVCE6-XovEkI1ZXgZnUOQxuAg" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#343a40"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="title" content="@yield('title', 'Messenger')">
    <title>@yield('title', 'Messenger')</title>
    @if(auth()->check() && messenger()->getProviderMessenger()->dark_mode)
        <link id="main_css" href="{{ mix("css/dark.css") }}" rel="stylesheet">
    @else
        <link id="main_css" href="{{ mix("css/app.css") }}" rel="stylesheet">
    @endif
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
@include('scripts')
</body>
</html>