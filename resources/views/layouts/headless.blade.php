<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/select2.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/v/bs5/jq-3.6.0/dt-1.12.1/b-2.2.3/r-2.3.0/sl-1.4.0/datatables.min.css" />
    <link href="{{ asset('css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/datepicker.css') }}" rel="stylesheet">

    <script src="{{ mix('mix/js/app.js') }}" defer></script>
    <link href="https://printjs-4de6.kxcdn.com/print.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script type="text/javascript"
        src="https://cdn.datatables.net/v/bs5/jq-3.6.0/dt-1.12.1/b-2.2.3/r-2.3.0/sl-1.4.0/datatables.min.js"
        integrity="sha384-oIm84zB4LoO9jOK4PI9B5Ef+b8HzN59uXaOV3uRDrBYlnamDHBJsNgZCnSeFTXuQ" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.4.0/dist/echarts.min.js"
        integrity="sha384-FjyteSbPW7vs5Dr3jhvKKYWERyIHYjoBAlrdMOAyfa538w74KFPNjsQoNXR4mPYt" crossorigin="anonymous">
    </script>
    <script src="{{ asset('js/select2.min.js') }}"></script>
    <script src="{{ asset('js/datepicker.min.js') }}"></script>

    <style>
        /* Roboto Fonts*/
        @font-face {
            font-family: "Roboto";
            font-style: normal;
            font-weight: 900;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-Black.ttf ') }}') format("truetype");
        }

        @font-face {
            font-family: "Roboto";
            font-style: italic;
            font-weight: 900;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-BlackItalic.ttf ') }}') format("truetype");
        }

        @font-face {
            font-family: "Roboto";
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-Bold.ttf') }}') format("truetype");

        }

        @font-face {
            font-family: "Roboto";
            font-style: italic;
            font-weight: 700;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-BoldItalic.ttf') }}') format("truetype");
        }

        @font-face {
            font-family: "Roboto";
            font-style: normal;
            font-weight: 500;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-Medium.ttf') }}') format("truetype");
        }

        @font-face {
            font-family: "Roboto";
            font-style: italic;
            font-weight: 500;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-MediumItalic.ttf') }}') format("truetype");
        }

        @font-face {
            font-family: "Roboto";
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-Regular.ttf') }}') format("truetype");
        }

        @font-face {
            font-family: "Roboto";
            font-style: italic;
            font-weight: 400;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-Italic.ttf') }}') format("truetype");
        }

        @font-face {
            font-family: "Roboto";
            font-style: normal;
            font-weight: 300;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-Light.ttf') }}') format("truetype");
        }

        @font-face {
            font-family: "Roboto";
            font-style: italic;
            font-weight: 300;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-LightItalic.ttf') }}') format("truetype");
        }

        @font-face {
            font-family: "Roboto";
            font-style: normal;
            font-weight: 100;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-Thin.ttf') }}') format("truetype");
        }

        @font-face {
            font-family: "Roboto";
            font-style: italic;
            font-weight: 100;
            font-display: swap;
            src: url('{{ asset('/css/fonts/Roboto/Roboto-ThinItalic.ttf') }}') format("truetype");
        }

        /*Roboto Fonts End*/
    </style>

</head>

@yield('content')


</html>