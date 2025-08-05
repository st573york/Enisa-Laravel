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

    @livewireStyles()

</head>

<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
    <symbol id="check-circle-fill" fill="currentColor" viewBox="0 0 16 16">
        <path
            d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
    </symbol>
    <symbol id="info-fill" fill="currentColor" viewBox="0 0 16 16">
        <path
            d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z" />
    </symbol>
    <symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16">
        <path
            d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
    </symbol>
</svg>

@if (!isset($action) || (isset($action) && $action != 'export'))
    <div dusk="loader" class="loader">
        <div class="spinner">
            <img src="/images/loader-enisa.gif" />
            <div class="loader-dot">Loading<span class="loader__dot">.</span><span class="loader__dot">.</span><span
                    class="loader__dot">.</span></div>
        </div>
    </div>
    <div data-nosnippet="true"
        class="globan globan-dropdown-collapsed dark logo-flag d-flex justify-content-between align-items-center"
        id="globan" style="z-index: 40;">
        <div class="globan-center">
            <div class="globan-content">
                <span>An official website of the European Union</span>
                <span>An official EU website</span>
                <a class="wt-link" href="javascript:;" aria-controls="globan-dropdown-nqacis1thwg"
                    aria-expanded="false">
                    <span>How do you know?</span>
                </a>
            </div>
            <div id="globan-dropdown-enisa" class="globan-dropdown" aria-hidden="true">
                <p class="wt-paragraph">All official European Union website addresses are in the <b>europa.eu</b>.</p>
                <p class="wt-paragraph">
                    <a class="wt-link"
                        href="https://european-union.europa.eu/institutions-law-budget/institutions-and-bodies/institutions-and-bodies-profiles_en"
                        rel="noopener" target="_blank">
                        See all EU institutions and bodies
                    </a>
                </p>
            </div>
        </div>
        <div id="language-switcher">
            <div class="icon wrapper">
                <span class="ri-span">EN</span>
                <img class="languange-icon" src="/images/languange-icon.svg" alt="languange icon">
            </div>
            <div role="group" id="languange-group" class="visible">
                <ul class="wrapper language-list" role="listbox" aria-label="language switcher">
                    @foreach ($languages as $language => $code)
                        <li role="option" class="item">
                            <span class="text">{{ $language }}<span
                                    class="country-code">{{ $code }}</span></span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif

<header>
    <nav class="navbar navbar-expand-xl navbar-light bg-white">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <img src="/images/enisa_logo.svg" alt="Enisa Logo">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown"
                aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end pe-5 w-100 collapse" id="navbarNavDropdown">
                <ul class="navbar-nav">
                    @if (!Auth::user()->blocked)
                        @if (Auth::user()->isAdmin() || Auth::user()->isPoC())
                            @php
                                $pattern = '/^\/index\/(access|report\/export_data)/';
                                $active = preg_match($pattern, request()->getRequestUri()) ? 'active' : '';
                            @endphp
                            <li
                                class="nav-item dropdown d-flex flex-column account-item justify-content-end align-items-end">
                                <a class="nav-link dropdown-toggle {{ $active }}" href="#"
                                    id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">Index Reports & Visuals</a>
                                <ul class="dropdown-menu float-end shadow" aria-labelledby="navbarDropdownMenuLink">
                                    <li>
                                        <a class="dropdown-item text-start {{ request()->is('index/access') ? 'active-shadow' : '' }}"
                                            href="/index/access">Visualisations</a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-start {{ request()->is('index/report/export_data') ? 'active-shadow' : '' }}"
                                            href="/index/report/export_data">Reports & Data</a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                        @if (Auth::user()->isPoC() || Auth::user()->isOperator())
                            @php
                                $pattern = '/^\/questionnaire\/(management|view|dashboard\/management|dashboard\/summarydata)/';
                                $active = preg_match($pattern, request()->getRequestUri()) ? 'active' : '';
                            @endphp
                            <li
                                class="nav-item dropdown d-flex flex-column account-item justify-content-end align-items-end">
                                <a dusk="surveys" class="nav-link dropdown-toggle {{ $active }}"
                                    href="/questionnaire/management" id="navbarDropdownMenuLink" role="button"
                                    aria-expanded="false">
                                    Surveys
                                </a>
                            </li>
                        @endif
                        @if (Auth::user()->isAdmin() || Auth::user()->isPoC())
                            @php
                                $pattern = '/^\/index\/(management|show|survey\/configuration\/management|indicator\/survey|datacollection)|user\/management|invitation\/management|questionnaire\/(admin\/management|admin\/dashboard' . (Auth::user()->isAdmin() ? '|view|dashboard\/summarydata' : '') . ')|audit/';
                                $active = preg_match($pattern, request()->getRequestUri()) ? 'active' : '';
                            @endphp
                            <li
                                class="nav-item dropdown d-flex flex-column account-item justify-content-end align-items-end">
                                <a dusk="management" class="nav-link dropdown-toggle {{ $active }}" href="#"
                                    id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">Management</a>
                                <ul class="dropdown-menu float-end shadow" aria-labelledby="navbarDropdownMenuLink">
                                    @if (Auth::user()->isAdmin())
                                        @php
                                            $index_management_active = preg_match('/^\/index\/(management|show).*/', request()->getRequestUri()) ? 'active-shadow' : '';
                                            $index_and_survey_configuration_active = preg_match('/^\/index\/(survey\/configuration\/management|indicator\/survey).*/', request()->getRequestUri()) ? 'active-shadow' : '';
                                        @endphp
                                        <li>
                                            <a dusk="indexes" class="dropdown-item text-start {{ $index_management_active }}"
                                                href="/index/management">Indexes</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-start {{ $index_and_survey_configuration_active }}"
                                                href="/index/survey/configuration/management">Index & Survey Configuration</a>
                                        </li>
                                        @php
                                            $questionnaire_admin_management_active = preg_match('/^\/questionnaire\/(view|admin\/management|admin\/dashboard|dashboard\/summarydata).*/', request()->getRequestUri()) ? 'active-shadow' : '';
                                        @endphp
                                        <li>
                                            <a dusk="surveys" class="dropdown-item text-start {{ $questionnaire_admin_management_active }}"
                                                href="/questionnaire/admin/management">Surveys</a>
                                        </li>
                                    @endif
                                    <li>
                                        <a dusk="users" class="dropdown-item text-start {{ request()->is('user/management') ? 'active-shadow' : '' }}"
                                            href="/user/management">Users</a>
                                    </li>
                                    @if (Auth::user()->isAdmin() || Auth::user()->isPrimaryPoC())
                                        <li>
                                            <a dusk="invitations" class="dropdown-item text-start {{ request()->is('invitation/management') ? 'active-shadow' : '' }}"
                                                href="/invitation/management">Invitations</a>
                                        </li>
                                    @endif
                                    @if (Auth::user()->isAdmin())
                                        <li>
                                            <a class="dropdown-item text-start {{ request()->is('index/datacollection') ? 'active-shadow' : '' }}"
                                                href="/index/datacollection">Data Collection</a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-start {{ request()->is('audit') ? 'active-shadow' : '' }}"
                                                href="/audit">Auditing</a>
                                        </li>
                                    @endif
                                </ul>
                            </li>
                        @endif
                        <li
                            class="nav-item dropdown d-flex flex-column account-item justify-content-end align-items-end">
                            <a dusk="documents" class="nav-link dropdown-toggle {{ request()->is('documents-library') ? 'active active-shadow' : '' }}"
                                href="/documents-library" id="navbarDropdownMenuLink" role="button"
                                aria-expanded="false">
                                Documents
                            </a>
                        </li>
                        <li
                            class="nav-item dropdown d-flex flex-column account-item justify-content-end align-items-end">
                            <a class="nav-link dropdown-toggle {{ request()->is('about-eu-csi') ? 'active active-shadow' : '' }}"
                                href="/about-eu-csi" id="navbarDropdownMenuLink" role="button"
                                aria-expanded="false">
                                About EU-CSI
                            </a>
                        </li>
                    @endif
                    <li class="nav-item dropdown d-flex flex-column  account-item justify-content-end align-items-end">
                        <a dusk="user" class="nav-link admin-icon dropdown-toggle" href="#" id="navbarDropdownMenuLink"
                            role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu shadow" aria-labelledby="navbarDropdownMenuLink">
                            <li>
                                <a dusk="my_account" class="dropdown-item text-start {{ request()->is('my-account') ? 'active-shadow' : '' }}"
                                    href="/my-account">My account</a>
                            </li>
                            <li>
                                <a dusk="logout" class="dropdown-item text-start" href="/logout">{{ __('Log Out') }}</a>
                                <!--
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-start">
                                        {{ __('Log Out') }}
                                    </button>
                                </form>
                                -->
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

@livewireScripts()

@yield('content')

@if (!isset($action) || (isset($action) && $action != 'export'))
    <footer id="enisa-footer">
        <button dusk="button_top" class="btn-top d-none"> 
            <svg xmlns="http://www.w3.org/2000/svg" height="55" viewBox="0 0 1792 1792" width="55"><path d="M1293 1139l102-102q19-19 19-45t-19-45l-454-454q-19-19-45-19t-45 19l-454 454q-19 19-19 45t19 45l102 102q19 19 45 19t45-19l307-307 307 307q19 19 45 19t45-19zm371-243q0 209-103 385.5t-279.5 279.5-385.5 103-385.5-103-279.5-279.5-103-385.5 103-385.5 279.5-279.5 385.5-103 385.5 103 279.5 279.5 103 385.5z"/>
            </svg>
        </button>

        <button dusk="button_bottom" class="btn-bottom d-none"> 
            <svg xmlns="http://www.w3.org/2000/svg" height="55" viewBox="0 0 1792 1792" width="55"><path d="M1293 1139l102-102q19-19 19-45t-19-45l-454-454q-19-19-45-19t-45 19l-454 454q-19 19-19 45t19 45l102 102q19 19 45 19t45-19l307-307 307 307q19 19 45 19t45-19zm371-243q0 209-103 385.5t-279.5 279.5-385.5 103-385.5-103-279.5-279.5-103-385.5 103-385.5 279.5-279.5 385.5-103 385.5 103 279.5 279.5 103 385.5z"/>
            </svg>
        </button>
        <div class="container-fluid">
            <div class="row">
                <div
                    class="col-md-5 offset-1 offset-md-unset d-flex flex-column justify-content-end ps-0 breakpoint-text-center">
                    <a href="/documents-library/download/data-privacy-statement.pdf">Data Privacy Statement</a>
                    <a href="/documents-library/download/end-user-statement.pdf">End User Statement</a>
                    <a href="/contacts">Contact</a>
                </div>

                <div class="col-md-5 d-flex flex-column justify-content-end ps-0 breakpoint-text-center">
                    <div
                        class="social d-flex flex-xl-row flex-md-column flex-sm-column flex-xs-column justify-content-end align-items-end">
                        <span class="me-3 connect text-end">Connect with ENISA :</span>
                        <div class="social-wrapper d-flex align-items-end mt-3">
                            <a href="https://www.facebook.com/ENISAEUAGENCY" rel="noopener" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                    viewBox="0 0 32 32" fill="none">
                                    <path
                                        d="M32 16C32 7.16344 24.8366 0 16 0C7.16344 0 0 7.16344 0 16C0 23.9859 5.85094 30.6053 13.5 31.8056V20.625H9.4375V16H13.5V12.475C13.5 8.465 15.8888 6.25 19.5434 6.25C21.2934 6.25 23.125 6.5625 23.125 6.5625V10.5H21.1075C19.12 10.5 18.5 11.7334 18.5 13V16H22.9375L22.2281 20.625H18.5V31.8056C26.1491 30.6053 32 23.9859 32 16Z"
                                        fill="#141414" />
                                </svg>
                            </a>
                            <a href="https://twitter.com/enisa_eu" rel="noopener" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="26"
                                    viewBox="0 0 32 26" fill="none">
                                    <path
                                        d="M10.0669 26C22.1394 26 28.7444 15.9956 28.7444 7.32248C28.7444 7.04123 28.7381 6.75373 28.7256 6.47248C30.0105 5.54328 31.1193 4.39234 32 3.07373C30.8034 3.60613 29.5329 3.95384 28.2319 4.10498C29.6017 3.28388 30.6274 1.99396 31.1187 0.474356C29.8301 1.23808 28.4208 1.77682 26.9513 2.06748C25.9611 1.01541 24.652 0.318822 23.2262 0.0854013C21.8005 -0.148019 20.3376 0.0947339 19.0637 0.776129C17.7897 1.45752 16.7757 2.53961 16.1785 3.85508C15.5812 5.17056 15.4339 6.64615 15.7594 8.05373C13.15 7.92279 10.5972 7.24494 8.26664 6.06413C5.93604 4.88332 3.87959 3.22592 2.23062 1.19936C1.39253 2.64432 1.13608 4.35419 1.51337 5.98145C1.89067 7.60872 2.87342 9.03126 4.26188 9.95998C3.2195 9.92689 2.19997 9.64624 1.2875 9.14123V9.22248C1.28657 10.7389 1.8108 12.2088 2.77108 13.3824C3.73136 14.5559 5.06843 15.3607 6.555 15.66C5.58941 15.9242 4.57598 15.9627 3.59313 15.7725C4.01261 17.0766 4.82876 18.2172 5.92769 19.0351C7.02662 19.853 8.35349 20.3075 9.72313 20.335C7.3979 22.1615 4.52557 23.1522 1.56875 23.1475C1.04438 23.1467 0.520532 23.1145 0 23.0512C3.00381 24.9783 6.49804 26.0018 10.0669 26Z"
                                        fill="#141414" />
                                </svg>
                            </a>
                            <a href="https://www.linkedin.com/organization-guest/company/european-union-agency-for-cybersecurity-enisa"
                                rel="noopener" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"
                                    viewBox="0 0 32 32" fill="none">
                                    <path
                                        d="M29.6313 0H2.3625C1.05625 0 0 1.03125 0 2.30625V29.6875C0 30.9625 1.05625 32 2.3625 32H29.6313C30.9375 32 32 30.9625 32 29.6938V2.30625C32 1.03125 30.9375 0 29.6313 0ZM9.49375 27.2687H4.74375V11.9937H9.49375V27.2687ZM7.11875 9.9125C5.59375 9.9125 4.3625 8.68125 4.3625 7.1625C4.3625 5.64375 5.59375 4.4125 7.11875 4.4125C8.6375 4.4125 9.86875 5.64375 9.86875 7.1625C9.86875 8.675 8.6375 9.9125 7.11875 9.9125ZM27.2687 27.2687H22.525V19.8438C22.525 18.075 22.4937 15.7937 20.0562 15.7937C17.5875 15.7937 17.2125 17.725 17.2125 19.7188V27.2687H12.475V11.9937H17.025V14.0813H17.0875C17.7188 12.8813 19.2688 11.6125 21.575 11.6125C26.3813 11.6125 27.2687 14.775 27.2687 18.8875V27.2687Z"
                                        fill="#141414" />
                                </svg>
                            </a>
                            <a href="https://www.youtube.com/user/ENISAvideos" rel="noopener" target="_blank">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="24"
                                    viewBox="0 0 32 24" fill="none">
                                    <path
                                        d="M31.6812 5.60015C31.6812 5.60015 31.3688 3.3939 30.4062 2.42515C29.1875 1.15015 27.825 1.1439 27.2 1.0689C22.725 0.743896 16.0063 0.743896 16.0063 0.743896H15.9937C15.9937 0.743896 9.275 0.743896 4.8 1.0689C4.175 1.1439 2.8125 1.15015 1.59375 2.42515C0.63125 3.3939 0.325 5.60015 0.325 5.60015C0.325 5.60015 0 8.1939 0 10.7814V13.2064C0 15.7939 0.31875 18.3876 0.31875 18.3876C0.31875 18.3876 0.63125 20.5939 1.5875 21.5626C2.80625 22.8376 4.40625 22.7939 5.11875 22.9314C7.68125 23.1751 16 23.2501 16 23.2501C16 23.2501 22.725 23.2376 27.2 22.9189C27.825 22.8439 29.1875 22.8376 30.4062 21.5626C31.3688 20.5939 31.6812 18.3876 31.6812 18.3876C31.6812 18.3876 32 15.8001 32 13.2064V10.7814C32 8.1939 31.6812 5.60015 31.6812 5.60015ZM12.6938 16.1501V7.1564L21.3375 11.6689L12.6938 16.1501Z"
                                        fill="#141414" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-5 offset-1 p-0">
                    <div class="line"></div>
                </div>
                <div class="col-5 p-0">
                    <div class="line"></div>
                </div>
            </div>
            <div class="subfooter row mt-3 pb-4 ">
                <div class="col-10 offset-1 footer-line mb-3"></div>
                <div class="col-5 d-flex offset-1 p-0">
                    @php
                        $year = intval(date('Y'));
                    @endphp
                    <span>© 2005-{{ $year }} by the European Union Agency for Cybersecurity.</span>
                </div>
                <div class="col-5 d-flex justify-content-end align-items-start">
                    <img src="/images/eu-flag.png" alt="European Union Flag" class="me-1">
                    <span>ENISA is an agency of the European Union.</span>
                </div>
                <div class="col-10 d-flex offset-1 p-0">
                    <span>EU Cybersecurity Index Platform v{{ config('app.version') }}</span>
                </div>
            </div>
        </div>
    </footer>
@endif

</html>
