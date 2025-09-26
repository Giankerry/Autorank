<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AutoRank')</title>

    <!-- Google Font Links -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,300..900;1,300..900&display=swap" rel="stylesheet">

    <!-- CSS Links -->
    <link rel="stylesheet" href="{{ asset('css/global-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/system-settings-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/view-all-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/kra-modal-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modal-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile-page-styles.css') }}">
    <link rel="stylesheet" href="{{ asset('css/responsive-styles.css') }}">

    <!-- Fontawesome CDN -->
    <script src="https://kit.fontawesome.com/5ba477d22e.js" crossorigin="anonymous"></script>
</head>

<body>
    @include('partials._navbar')
    @include('partials._action_modals')

    <main>
        @yield('content')
    </main>

    <script src="{{ asset('js/global-scripts.js') }}"></script>
    <script src="{{ asset('js/system-settings-scripts.js') }}"></script>

    @stack('page-scripts')
</body>

</html>