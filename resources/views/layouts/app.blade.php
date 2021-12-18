<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Customer Geocoder</title>

        <link href="{{ mix('css/app.css') }}" rel="stylesheet"></link>
        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    </head>
    <body>
        <div id="app">
            <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
                <div class="container-fluid">
                    <a class="navbar-brand" href="#">Customer Geocoder</a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <ul class="navbar-nav me-auto mb-2 mb-md-0">
                            <li class="nav-item">
                                <a class="nav-link active" aria-current="page" href="{{ route('imports.index') }}">Imports</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('imports.create') }}" aria-disabled="true">New Import</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            <main class="container content pt-5 mt-5">
                @yield('content')
            </main>
        </div>
    </body>

    <script src="{{ mix('js/app.js') }}" defer></script>
    @stack('scripts')
</html>
