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
   
    <script src="{{ mix('mix/js/app.js') }}" defer></script>
    <link href="https://printjs-4de6.kxcdn.com/print.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.2/css/all.min.css" rel="stylesheet">
    <script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/select2.min.js') }}"></script>

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


<section id="no-menu" class="d-flex flex-column align-items-center">
    <div class="container">
        <div class="row mb-5 mt-5 mb-5">
            <div class="col d-flex justify-content-center logo-wrap">
                <img src="/images/enisa_logo.svg" alt="Enisa Logo">
            </div>
        </div>
    </div>
</section>

@yield('content')

</body>

</html>
