<html>
    <head>
        <title>Baseball Stats - @yield('title')</title>
        <meta charset="utf-8">
        <link rel="stylesheet" href="/styles.css">
        <script src="/sorttable.js"></script>
        <script src="/jquery.min.js"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    </head>
    <body>
        <div class="container">
            @yield('content')
        </div>
    </body>
</html>