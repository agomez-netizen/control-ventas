<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'RIFA2026')</title>

    <!-- Bootstrap CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Estilos del login -->
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>

<body class="bg-light">

    @yield('content')

    <!-- Bootstrap JS -->
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>

    <!-- Chart.js (una sola vez) -->
    <script
        src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js">
    </script>

    <!-- JS propio -->
    <script src="{{ asset('js/app.js') }}"></script>

</body>
</html>
