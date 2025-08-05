@extends('layouts.headless')

<style>
    @page :first {
        margin: 0mm;
        size: A4;

        @top-center {
            content: " " !important;
        }

        @bottom-left {
            content: " " !important;
        }

        @bottom-center {
            content: " " !important;
        }

    }

    @page {
        size: A4;
        margin: 20mm 15mm;

        @top-center {
            content: element(headerRunning);
        }


        @bottom-left {
            content: element(footerRunning);
        }
    }

    #report-header {
        position: running(headerRunning);
        height: 67px;
        display: flex;
    }

    #report-footer {
        position: running(footerRunning);
        height: 60px;
        display: block;
    }


    .progress::after {
        display: inline-block;
        position: relative;
        right: -4px;
        content: var(--progress_value);
        color: black;
        font-size: 10px;
    }
    .progress:before {
        display: inline-block;
        position: relative;
        content: var(--country-code);
        color: black;
        width: 20px;
        font-size: 10px;
    }
    
</style>
{{-- Above style have to remain in blade so as to work dynamically --}}

@section('title', 'MS Reports')

@section('content')

<input type="hidden" id="index" value="{{ $index }}" class="d-none"/>

<div id="cover" class="d-no-print-none">
    <div class="d-flex flex-column">
        <div class="cover-header">
            <img class="report-logo pt-4 ps-4" src="/images/enisa_logo.svg" alt="Enisa Logo">
        </div>
         <div class="cover-photo">
            <img class="cover-img" src="/images/cover-img.png" alt="Enisa Logo">
        </div>
        <div class="cover-content">
            <div class="d-flex flex-column justify-content-between p-5">
                <h1>EU Cybersecurity Index</h1>
                <div class="eu-report d-flex justify-content-between">
                    <div>
                        <h4>Country report for <strong>{{$data['country']}}</strong></h4>
                        <h5>
                            <!-- ({{$data['date']}}) -->
                            July 2024
                        </h5>
                    </div>
                    <h5 class="align-self-end">TLP:AMBER</h5>
                </div>
             </div>
        </div>
    </div>
</div>

<main class="reports">
    
    <div id="report-header">
        <div class="container-fluid reports mt-1">
            <section class="row">
                <div class="col-sm-8 col-sm-8">
                    <div class="d-flex flex-column align-items-start">
                        <p class="fw-light mb-0 mt-2">Country report for <strong>{{$data['country']}}</strong></p>
                        <!-- <span class="report-date">({{$data['date']}})</span> -->
                        <span class="report-date">July 2024</span>
                    </div>
                </div>
                <div class="col-sm-4 mb-4 d-flex justify-content-end">
                    <img class="report-logo" src="/images/enisa_logo.svg" alt="Enisa Logo">
                </div>
            </section>
        </div>
    </div>
    <div id="report-footer">
        <div class="container-fluid reports p-0 mt-0">
        </div>
    </div>
   
    <div class="container-fluid">
{{-- Count subareas and string with area names --}}
<?php
        $count_subareas = 0;
        $areas_names = [];

        foreach ($data["areas"] as $area)
        {
            array_push($areas_names, $area["name"]);
            foreach($area["subareas"] as $subarea ) {
                $count_subareas += 1;
            }
        }
        
        $areas_names_string = implode(', ', $areas_names);
?>
        <section class="row"> 
            <div class="col-s12">
                <h2>EU Cybersecurity Index</h2>
            </div>
        </section>
        <section id="report-intro" class="row mt-2">
            <div class="col-12 speed-legend">
                <h3 class="line">Introduction</h3>
                
                <p>
                    The <strong>Cybersecurity Index (EU-CSI)</strong> provides insights on the cybersecurity posture of the European Union (EU) and Member States (MS) to support the informed decision-making on identified challenges and policy making in cybersecurity. The EU-CSI is a composite index, formed by a set of <strong>60 indicators</strong>, structured hierarchically across <strong>15 sub-areas</strong> and <strong>4 areas</strong> (Policy, Capacity, Market/Industry, Operations).
                    <br><br>
                    This report gives national policy-makers an overview of the level of cybersecurity of their country, and of their positioning in the EU context. In addition, it shows the EU's highest- and lowest-scoring indicators, respectively <strong>"Top-performing areas"</strong> and <strong>"Least-performing areas"</strong>. 
                    <br><br>
                    Finally, the index values and related data for each indicator are presented in the <strong>Annex</strong>.
                </p>
            </div>
        </section>

        <section id="legend" class="row mt-4">
            <div class="col-12  ">
                <div class="row">
                    <div class="col mb-4">
                        <h3>Legend</h3>
                        <p class="color-bar-description">Each colour corresponds to the positioning of a country with respect to the EU average. <strong>The number indicates the difference of the country's score from the EU average.</strong></p>
                    </div>
                </div>
                <div class="row deviation-legend mb-4">
                    <div class="col-sm-5 ">
                        <div class="d-flex gap-2 mobile-flex-start position-relative">
                            <div class="d-flex flex-column gap-2 example-ms">
                                <div class="diff-wrapper-sm  deviation-color lesser range-Low">
                                    <span>-11.71</span>
                                </div>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <div class="diff-wrapper-sm  deviation-color range-Med">
                                    <span>8.88</span>
                                </div>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                <div class="diff-wrapper-sm  deviation-color range-High">
                                    <span>14.88</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 speed-legend">
                        <p><strong>Difference of country score from EU average:</strong></p>
                    </div>
                </div>
                <div class="row mb-2 speed-legend">
                    <div class="col-2">
                        <div class="row mb-2">
                            <span class="speedometer-Low"><span class="speed-range">-11.71</span></span>
                        </div>
                        <div class="row mb-2">
                            <span class="speedometer-Med"><span class="speed-range">-8.88</span></span>
                        </div>
                        <div class="row mb-2">
                            <span class="speedometer-High"><span class="speed-range">14.88</span></span>
                        </div>
                    </div>
                    <div class="col-10">
                        <div class="row mb-2 h-50 align-items-center">
                            <p class="m-0">
                                The country has a score that is more than 10 units below the EU average. The number indicates the difference from the EU average.
                            </p>
                        </div>
                        <div class="row mb-2 h-50 align-items-center">
                            <p class="m-0">
                                The country has a score between 10 units above and 10 units below the EU average. The number indicates the difference from the EU average.
                            </p>
                        </div>
                        <div class="row mb-2 h-50 align-items-center">
                            <p class="m-0">
                                The country has a score that is at least 10 units above the EU average. The number indicates the difference from the EU average.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="disclaimers" class="row mt-3 page-break-after">
            <div class="col-12  ">
                <h3>Disclaimers</h3>
            </div>
        </section>

        <section id="report-overall-score" class="row mt-2">
            <div class="col-12">
                <div class="row">
                    <div class="col-12 col-sm-5 ps-0 mb-4">
                        <h3>Overall score</h3>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 ps-0">
                <div class="d-flex align-items-center gap-2">
                    <p class="m-0">{{$data['country']}} overall index value is <h4 class="m-0 euAverage-border">{{$data['scores']["country"]}}</h4></p>
                </div>
            </div>

            <div class="col-12 col-sm-6 offset-xs-1  ">
                <div class="row">
                    <div class="col-12">
                        {{-- Svg Map here --}}

                        <div id="svg-country-map" class="eu-color">
                            <svg xmlns="http://www.w3.org/2000/svg" width="254" height="254"
                                viewBox="0 0 254 254" fill="none">

                                {{!! $mapSvgPath !!}}

                            </svg>
                        </div>
                    </div>
                    {{-- <div class="col-5">
                        <div class="rank-difference h-100">
                            <div class="d-flex flex-column justify-content-center align-items-center h-100 gap-3">
                                <h4 class="m-0"> {{$data['scores']["country"]}}</h4>
                                <div class="diff-wrapper-lg deviation-color {!! GeneralHelper::calculateRange($data['scores']["difference"]) !!} ">
                                    <span>{{$data['scores']["difference"]}}</span>
                                </div>
                                <span class=" scores-subtitle">Difference from the EU Average</span>
                                </div>
                            </div>
                        </div>
                    </div> --}}
            </div>
        </section>

        <section id="target-areas" class="row mt-2">
                <div class="col-12  ">
                    <h3>Results by area</h3>
                    <p>This section shows the values of the index by area</p>
                </div>
               
                <div class="col-12 ">
                    <div class="row">
                        @foreach ($data['areas'] as $area)
                            <div class="ps-0 col-sm-3 target-area">
                                <div class="d-flex flex-column gap-2 justify-content-center align-items-center h-100">
                                    <img src="/images/areas-images/{{$loop->index}}.png" alt="area-image"> 
                                    {{-- print images for areas located in /images/area-image folder named by number convention counting from zero for first area found in json--}}
                                    <h6>{{$area["name"]}}</h6>
                                    <span class="area-description">
                                        {{$area["description"]}}
                                    </span>
                                    <div class="d-flex gap-2 align-items-center speedometer-wrap">
                                        
                                        @if ($area['scores'] != null)
                                            <div class="d-flex gap-1">
                                                <div class="d-flex flex-column">
                                                    <span  class="speedometer-upper">{{$data['countryCode']}} SCORE</span>
                                                    <span>{{$area['scores']['country']}}</span>
                                                </div>
                                                <span class="speedometer-{{$area['scores']['speedometer']}}"><span class="speed-range">{{$area['scores']['difference']}}</span></span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                         @endforeach
                    </div>
                </div>
        </section>

        <section class="report-overall-areas-subareas row mt-5 page-break-after">
                <div class="col-12 ">
                    <p>This section compares the results by area with the EU average.</p>
                </div>
                <div class="col-12 mt-2">
                    @foreach ($data['areas'] as $area)
                    @if(!empty($area['scores']))
                    <div class="row mb-1">
                        <div class="col-12 col-sm-6">
                            <div class="d-flex gap-2 align-items-center mb-2">
                                <div class="diff-wrapper-sm area-diff-1 deviation-color {!! GeneralHelper::calculateRange($area["scores"]["difference"]) !!}">
                                    <span>{{ number_format($area["scores"]["difference"], 2)}}</span>
                                </div>
                                <span class="area-difference-title">{{$area["name"]}}</span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 offset-xs-1 ">
                            <div class="d-flex flex-column progress-wrap">
                                <div class="progress" style="--progress_value: '{{ $area["scores"]["country"]}}'; --country-code: '{{ $data["countryCode"]}}'; ">
                                    <div class="progress-bar {!! GeneralHelper::calculateRange($area["scores"]["difference"]) !!}" role="progressbar" style="width: {{ $area["scores"]["country"]}}%" aria-valuenow="{{ $area["scores"]["country"]}}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="progress"  style="--progress_value: '{{ $area["scores"]["euAverage"]}}'; --country-code: 'EU'; ">
                                    <div class="progress-bar range-Med" role="progressbar" style="width: {{ $area["scores"]["euAverage"]}}%" aria-valuenow="{{ $area["scores"]["euAverage"]}}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
            </section>

            <section class="report-results-areas-subareas row mt-2">
                <div class="col-12  pe-0">
                    @foreach ($data['areas'] as $area)
                        @if($loop->iteration === 1)
                            <h3>Results by areas and sub-areas</h3>
                        @endif
                                                        
                    <div class=" @if($loop->iteration % 2 == 0) page-break-after @endif ">
                        <div class="row">
                            @if(!empty($area['scores']))
                                <div class="col-sm-12">
                                    <div class="d-flex gap-2">
                                        <div class="diff-wrapper-lg deviation-color {!! GeneralHelper::calculateRange($area["scores"]["difference"]) !!} "><span class=" scores-difference">
                                            {{ number_format($area["scores"]["difference"], 2)}}</span>
                                        </div>
                                        <h3 class="no-line mt-4">{{$area["name"]}}</h3>
                                        <div class="d-flex gap-1 align-items-center mb-1">
                                            <div class="country-flag">
                                                <img src="{{$flagFile}}" width="17" height="11" viewBox="0 0 17 11"/>
                                            </div>
                                            <span
                                                class="country-area-score">{{ number_format($area["scores"]["country"], 2)}}</span>
                                            <div class="eu-flag ms-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="17" height="11" viewBox="0 0 17 11" fill="none">
                                                    <g clip-path="url(#clip0_1085_1586)">
                                                    <rect width="16" height="11" transform="translate(0.5)" fill="#004F9F"/>
                                                    <path d="M0.630615 2.39062H6.39252V3.51883H0.630615V2.39062Z" fill="#004F9F"/>
                                                    <path d="M8.03278 1L7.83972 1.64353L8.16149 1.70788L8.03278 1Z" fill="#FFCC00"/>
                                                    <path d="M8.03289 1L8.22595 1.64353L7.90419 1.70788L8.03289 1Z" fill="#FFCC00"/>
                                                    <path d="M8.64486 1.44484L7.97317 1.46009L8.0114 1.78599L8.64486 1.44484Z" fill="#FFCC00"/>
                                                    <path d="M8.64481 1.44481L8.09243 1.82728L7.9318 1.54115L8.64481 1.44481Z" fill="#FFCC00"/>
                                                    <path d="M8.41107 2.16426L8.189 1.53015L7.89086 1.66722L8.41107 2.16426Z" fill="#FFCC00"/>
                                                    <path d="M8.41119 2.16435L7.87675 1.75721L8.09924 1.51601L8.41119 2.16435Z" fill="#FFCC00"/>
                                                    <path d="M7.42093 1.44484L8.09262 1.46009L8.0544 1.78599L7.42093 1.44484Z" fill="#FFCC00"/>
                                                    <path d="M7.42099 1.44481L7.97336 1.82728L8.134 1.54115L7.42099 1.44481Z" fill="#FFCC00"/>
                                                    <path d="M7.65472 2.16426L7.87679 1.53015L8.17493 1.66722L7.65472 2.16426Z" fill="#FFCC00"/>
                                                    <path d="M7.65473 2.16435L8.18917 1.75721L7.96668 1.51601L7.65473 2.16435Z" fill="#FFCC00"/>
                                                    <path d="M8.03278 8.72217L7.83972 9.3657L8.16149 9.43005L8.03278 8.72217Z" fill="#FFCC00"/>
                                                    <path d="M8.03289 8.72217L8.22595 9.3657L7.90419 9.43005L8.03289 8.72217Z" fill="#FFCC00"/>
                                                    <path d="M8.64486 9.16701L7.97317 9.18226L8.0114 9.50816L8.64486 9.16701Z" fill="#FFCC00"/>
                                                    <path d="M8.64481 9.16698L8.09243 9.54945L7.9318 9.26332L8.64481 9.16698Z" fill="#FFCC00"/>
                                                    <path d="M8.41107 9.88643L8.189 9.25232L7.89086 9.38939L8.41107 9.88643Z" fill="#FFCC00"/>
                                                    <path d="M8.41119 9.88652L7.87675 9.47937L8.09924 9.23818L8.41119 9.88652Z" fill="#FFCC00"/>
                                                    <path d="M7.42093 9.16701L8.09262 9.18226L8.0544 9.50816L7.42093 9.16701Z" fill="#FFCC00"/>
                                                    <path d="M7.42099 9.16698L7.97336 9.54945L8.134 9.26332L7.42099 9.16698Z" fill="#FFCC00"/>
                                                    <path d="M7.65472 9.88643L7.87679 9.25232L8.17493 9.38939L7.65472 9.88643Z" fill="#FFCC00"/>
                                                    <path d="M7.65473 9.88652L8.18917 9.47937L7.96668 9.23818L7.65473 9.88652Z" fill="#FFCC00"/>
                                                    <path d="M4.1717 4.86133L3.97864 5.50486L4.3004 5.56921L4.1717 4.86133Z" fill="#FFCC00"/>
                                                    <path d="M4.17169 4.86133L4.36475 5.50486L4.04298 5.56921L4.17169 4.86133Z" fill="#FFCC00"/>
                                                    <path d="M4.78378 5.30617L4.11209 5.32142L4.15032 5.64732L4.78378 5.30617Z" fill="#FFCC00"/>
                                                    <path d="M4.78373 5.30614L4.23135 5.68861L4.07072 5.40248L4.78373 5.30614Z" fill="#FFCC00"/>
                                                    <path d="M4.54987 6.02559L4.3278 5.39148L4.02966 5.52855L4.54987 6.02559Z" fill="#FFCC00"/>
                                                    <path d="M4.54974 6.02568L4.0153 5.61853L4.23778 5.37734L4.54974 6.02568Z" fill="#FFCC00"/>
                                                    <path d="M3.55973 5.30617L4.23142 5.32142L4.19319 5.64732L3.55973 5.30617Z" fill="#FFCC00"/>
                                                    <path d="M3.55966 5.30614L4.11203 5.68861L4.27267 5.40248L3.55966 5.30614Z" fill="#FFCC00"/>
                                                    <path d="M3.7934 6.02559L4.01546 5.39148L4.3136 5.52855L3.7934 6.02559Z" fill="#FFCC00"/>
                                                    <path d="M3.7934 6.02568L4.32784 5.61853L4.10535 5.37734L3.7934 6.02568Z" fill="#FFCC00"/>
                                                    <path d="M5.77155 2.71074L6.30599 2.30359L6.0835 2.0624L5.77155 2.71074Z" fill="#FFCC00"/>
                                                    <path d="M5.77142 2.71065L5.99349 2.07654L6.29163 2.21361L5.77142 2.71065Z" fill="#FFCC00"/>
                                                    <path d="M5.53781 1.99119L6.09018 2.37367L6.25082 2.08754L5.53781 1.99119Z" fill="#FFCC00"/>
                                                    <path d="M5.53775 1.99123L6.20945 2.00648L6.17122 2.33238L5.53775 1.99123Z" fill="#FFCC00"/>
                                                    <path d="M6.1496 1.54639L5.95654 2.18992L6.27831 2.25427L6.1496 1.54639Z" fill="#FFCC00"/>
                                                    <path d="M6.14971 1.54639L6.34277 2.18992L6.02101 2.25427L6.14971 1.54639Z" fill="#FFCC00"/>
                                                    <path d="M6.52789 2.71074L5.99345 2.30359L6.21593 2.0624L6.52789 2.71074Z" fill="#FFCC00"/>
                                                    <path d="M6.52789 2.71065L6.30582 2.07654L6.00769 2.21361L6.52789 2.71065Z" fill="#FFCC00"/>
                                                    <path d="M6.76163 1.99119L6.20926 2.37367L6.04862 2.08754L6.76163 1.99119Z" fill="#FFCC00"/>
                                                    <path d="M6.76168 1.99123L6.08999 2.00648L6.12822 2.33238L6.76168 1.99123Z" fill="#FFCC00"/>
                                                    <path d="M5.09638 4.14229L4.87431 3.50818L4.57617 3.64525L5.09638 4.14229Z" fill="#FFCC00"/>
                                                    <path d="M5.09637 4.14238L4.56193 3.73523L4.78442 3.49404L5.09637 4.14238Z" fill="#FFCC00"/>
                                                    <path d="M4.34003 4.14238L4.87447 3.73523L4.65199 3.49404L4.34003 4.14238Z" fill="#FFCC00"/>
                                                    <path d="M4.3399 4.14229L4.56197 3.50818L4.86011 3.64525L4.3399 4.14229Z" fill="#FFCC00"/>
                                                    <path d="M4.10617 3.42284L4.65854 3.80531L4.81918 3.51918L4.10617 3.42284Z" fill="#FFCC00"/>
                                                    <path d="M4.10587 3.42287L4.77756 3.43812L4.73933 3.76402L4.10587 3.42287Z" fill="#FFCC00"/>
                                                    <path d="M5.33011 3.42284L4.77774 3.80531L4.6171 3.51918L5.33011 3.42284Z" fill="#FFCC00"/>
                                                    <path d="M5.33017 3.42287L4.65847 3.43812L4.6967 3.76402L5.33017 3.42287Z" fill="#FFCC00"/>
                                                    <path d="M4.7182 2.97852L4.91125 3.62205L4.58949 3.6864L4.7182 2.97852Z" fill="#FFCC00"/>
                                                    <path d="M4.71821 2.97852L4.52515 3.62205L4.84691 3.6864L4.71821 2.97852Z" fill="#FFCC00"/>
                                                    <path d="M5.3309 7.29299L4.65921 7.30824L4.69743 7.63414L5.3309 7.29299Z" fill="#FFCC00"/>
                                                    <path d="M5.33109 7.29295L4.77871 7.67542L4.61808 7.38929L5.33109 7.29295Z" fill="#FFCC00"/>
                                                    <path d="M5.09711 8.01289L4.87504 7.37879L4.5769 7.51585L5.09711 8.01289Z" fill="#FFCC00"/>
                                                    <path d="M5.09711 8.01299L4.56266 7.60584L4.78515 7.36465L5.09711 8.01299Z" fill="#FFCC00"/>
                                                    <path d="M4.34064 8.0125L4.87508 7.60535L4.6526 7.36416L4.34064 8.0125Z" fill="#FFCC00"/>
                                                    <path d="M4.34064 8.0124L4.56271 7.3783L4.86084 7.51537L4.34064 8.0124Z" fill="#FFCC00"/>
                                                    <path d="M4.71881 6.84863L4.91187 7.49216L4.5901 7.55652L4.71881 6.84863Z" fill="#FFCC00"/>
                                                    <path d="M4.71894 6.84863L4.52588 7.49216L4.84764 7.55652L4.71894 6.84863Z" fill="#FFCC00"/>
                                                    <path d="M4.10697 7.29347L4.77866 7.30872L4.74043 7.63463L4.10697 7.29347Z" fill="#FFCC00"/>
                                                    <path d="M4.1069 7.29344L4.65927 7.67591L4.81991 7.38978L4.1069 7.29344Z" fill="#FFCC00"/>
                                                    <path d="M6.72763 8.5884L6.05594 8.60365L6.09416 8.92955L6.72763 8.5884Z" fill="#FFCC00"/>
                                                    <path d="M6.72757 8.58836L6.1752 8.97083L6.01456 8.6847L6.72757 8.58836Z" fill="#FFCC00"/>
                                                    <path d="M6.49384 9.3083L6.27177 8.6742L5.97363 8.81126L6.49384 9.3083Z" fill="#FFCC00"/>
                                                    <path d="M6.49383 9.3084L5.95939 8.90125L6.18188 8.66006L6.49383 9.3084Z" fill="#FFCC00"/>
                                                    <path d="M5.73737 9.3084L6.27181 8.90125L6.04932 8.66006L5.73737 9.3084Z" fill="#FFCC00"/>
                                                    <path d="M5.73724 9.3083L5.95931 8.6742L6.25745 8.81126L5.73724 9.3083Z" fill="#FFCC00"/>
                                                    <path d="M6.11541 8.14355L6.30847 8.78708L5.98671 8.85144L6.11541 8.14355Z" fill="#FFCC00"/>
                                                    <path d="M6.11542 8.14355L5.92236 8.78708L6.24413 8.85144L6.11542 8.14355Z" fill="#FFCC00"/>
                                                    <path d="M5.50345 8.5884L6.17514 8.60365L6.13692 8.92955L5.50345 8.5884Z" fill="#FFCC00"/>
                                                    <path d="M5.50338 8.58836L6.05576 8.97083L6.21639 8.6847L5.50338 8.58836Z" fill="#FFCC00"/>
                                                    <path d="M11.8941 4.86133L12.0872 5.50486L11.7654 5.56921L11.8941 4.86133Z" fill="#FFCC00"/>
                                                    <path d="M11.8941 4.86133L11.701 5.50486L12.0228 5.56921L11.8941 4.86133Z" fill="#FFCC00"/>
                                                    <path d="M11.282 5.30617L11.9537 5.32142L11.9155 5.64732L11.282 5.30617Z" fill="#FFCC00"/>
                                                    <path d="M11.2821 5.30614L11.8344 5.68861L11.9951 5.40248L11.2821 5.30614Z" fill="#FFCC00"/>
                                                    <path d="M11.5158 6.02559L11.7379 5.39148L12.036 5.52855L11.5158 6.02559Z" fill="#FFCC00"/>
                                                    <path d="M11.5158 6.02568L12.0503 5.61853L11.8278 5.37734L11.5158 6.02568Z" fill="#FFCC00"/>
                                                    <path d="M12.5061 5.30617L11.8344 5.32142L11.8726 5.64732L12.5061 5.30617Z" fill="#FFCC00"/>
                                                    <path d="M12.5061 5.30614L11.9538 5.68861L11.7931 5.40248L12.5061 5.30614Z" fill="#FFCC00"/>
                                                    <path d="M12.2723 6.02559L12.0502 5.39148L11.7521 5.52855L12.2723 6.02559Z" fill="#FFCC00"/>
                                                    <path d="M12.2724 6.02568L11.738 5.61853L11.9604 5.37734L12.2724 6.02568Z" fill="#FFCC00"/>
                                                    <path d="M10.2942 2.71074L9.7598 2.30359L9.98229 2.0624L10.2942 2.71074Z" fill="#FFCC00"/>
                                                    <path d="M10.2944 2.71065L10.0723 2.07654L9.77417 2.21361L10.2944 2.71065Z" fill="#FFCC00"/>
                                                    <path d="M10.528 1.99119L9.97561 2.37367L9.81498 2.08754L10.528 1.99119Z" fill="#FFCC00"/>
                                                    <path d="M10.528 1.99123L9.85635 2.00648L9.89458 2.33238L10.528 1.99123Z" fill="#FFCC00"/>
                                                    <path d="M9.91607 1.54639L10.1091 2.18992L9.78737 2.25427L9.91607 1.54639Z" fill="#FFCC00"/>
                                                    <path d="M9.91596 1.54639L9.7229 2.18992L10.0447 2.25427L9.91596 1.54639Z" fill="#FFCC00"/>
                                                    <path d="M9.5379 2.71074L10.0723 2.30359L9.84986 2.0624L9.5379 2.71074Z" fill="#FFCC00"/>
                                                    <path d="M9.5379 2.71065L9.75997 2.07654L10.0581 2.21361L9.5379 2.71065Z" fill="#FFCC00"/>
                                                    <path d="M9.30404 1.99119L9.85642 2.37367L10.0171 2.08754L9.30404 1.99119Z" fill="#FFCC00"/>
                                                    <path d="M9.30411 1.99123L9.9758 2.00648L9.93758 2.33238L9.30411 1.99123Z" fill="#FFCC00"/>
                                                    <path d="M10.9694 4.14229L11.1915 3.50818L11.4896 3.64525L10.9694 4.14229Z" fill="#FFCC00"/>
                                                    <path d="M10.9694 4.14238L11.5039 3.73523L11.2814 3.49404L10.9694 4.14238Z" fill="#FFCC00"/>
                                                    <path d="M11.7258 4.14238L11.1913 3.73523L11.4138 3.49404L11.7258 4.14238Z" fill="#FFCC00"/>
                                                    <path d="M11.7259 4.14229L11.5038 3.50818L11.2057 3.64525L11.7259 4.14229Z" fill="#FFCC00"/>
                                                    <path d="M11.9595 3.42332L11.4071 3.8058L11.2465 3.51966L11.9595 3.42332Z" fill="#FFCC00"/>
                                                    <path d="M11.9597 3.42336L11.288 3.43861L11.3262 3.76451L11.9597 3.42336Z" fill="#FFCC00"/>
                                                    <path d="M10.7356 3.42284L11.2879 3.80531L11.4486 3.51918L10.7356 3.42284Z" fill="#FFCC00"/>
                                                    <path d="M10.7355 3.42287L11.4072 3.43812L11.369 3.76402L10.7355 3.42287Z" fill="#FFCC00"/>
                                                    <path d="M11.3475 2.97852L11.1544 3.62205L11.4762 3.6864L11.3475 2.97852Z" fill="#FFCC00"/>
                                                    <path d="M11.3476 2.97852L11.5406 3.62205L11.2189 3.6864L11.3476 2.97852Z" fill="#FFCC00"/>
                                                    <path d="M10.7348 7.29299L11.4065 7.30824L11.3682 7.63414L10.7348 7.29299Z" fill="#FFCC00"/>
                                                    <path d="M10.7347 7.29295L11.2871 7.67542L11.4477 7.38929L10.7347 7.29295Z" fill="#FFCC00"/>
                                                    <path d="M10.9687 8.01289L11.1908 7.37879L11.4889 7.51585L10.9687 8.01289Z" fill="#FFCC00"/>
                                                    <path d="M10.9687 8.01299L11.5031 7.60584L11.2806 7.36465L10.9687 8.01299Z" fill="#FFCC00"/>
                                                    <path d="M11.7252 8.0125L11.1907 7.60535L11.4132 7.36416L11.7252 8.0125Z" fill="#FFCC00"/>
                                                    <path d="M11.7252 8.0124L11.5031 7.3783L11.205 7.51537L11.7252 8.0124Z" fill="#FFCC00"/>
                                                    <path d="M11.3469 6.84814L11.1538 7.49167L11.4756 7.55603L11.3469 6.84814Z" fill="#FFCC00"/>
                                                    <path d="M11.3469 6.84814L11.5399 7.49167L11.2182 7.55603L11.3469 6.84814Z" fill="#FFCC00"/>
                                                    <path d="M11.9588 7.29299L11.2871 7.30824L11.3254 7.63414L11.9588 7.29299Z" fill="#FFCC00"/>
                                                    <path d="M11.9589 7.29295L11.4065 7.67542L11.2459 7.38929L11.9589 7.29295Z" fill="#FFCC00"/>
                                                    <path d="M9.33817 8.5884L10.0099 8.60365L9.97163 8.92955L9.33817 8.5884Z" fill="#FFCC00"/>
                                                    <path d="M9.3381 8.58836L9.89048 8.97083L10.0511 8.6847L9.3381 8.58836Z" fill="#FFCC00"/>
                                                    <path d="M9.57196 9.3083L9.79403 8.6742L10.0922 8.81126L9.57196 9.3083Z" fill="#FFCC00"/>
                                                    <path d="M9.57196 9.3084L10.1064 8.90125L9.88392 8.66006L9.57196 9.3084Z" fill="#FFCC00"/>
                                                    <path d="M10.3284 9.3084L9.79398 8.90125L10.0165 8.66006L10.3284 9.3084Z" fill="#FFCC00"/>
                                                    <path d="M10.3286 9.3083L10.1065 8.6742L9.80834 8.81126L10.3286 9.3083Z" fill="#FFCC00"/>
                                                    <path d="M9.95038 8.14355L9.75732 8.78708L10.0791 8.85144L9.95038 8.14355Z" fill="#FFCC00"/>
                                                    <path d="M9.95025 8.14355L10.1433 8.78708L9.82155 8.85144L9.95025 8.14355Z" fill="#FFCC00"/>
                                                    <path d="M10.5622 8.5884L9.89053 8.60365L9.92876 8.92955L10.5622 8.5884Z" fill="#FFCC00"/>
                                                    <path d="M10.5623 8.58836L10.0099 8.97083L9.84928 8.6847L10.5623 8.58836Z" fill="#FFCC00"/>
                                                    </g>
                                                    <defs>
                                                    <clipPath id="clip0_1085_1586">
                                                    <rect width="16" height="11" fill="white" transform="translate(0.5)"/>
                                                    </clipPath>
                                                    </defs>
                                                </svg>
                                            </div>
                                            <span class="eu-area-score">{{ number_format($area["scores"]["euAverage"], 2)}}</span>
                                        </div>
                                    </div>
                                    <p class="mt-2">{{$area["description"]}}
                                    </p>
                                </div>
                            @endif
                        </div>
                        <div class="row pe-0">
                            @if(!empty($area["subareas"]))
                                <div class="col-12 col-sm-5 mb-4 pe-0">
                                    @foreach ( $area["subareas"] as $subarea )
                                    <div class="d-flex flex-column mb-3">
                                        <div class="d-flex gap-2 mb-2 codes-wrapper">
                                            <div class="d-flex gap-2">
                                                <div class="diff-wrapper-sm deviation-color {!! GeneralHelper::calculateRange($subarea["scores"]["difference"]) !!}">
                                                    <span class="scores-difference">{{ number_format($subarea["scores"]["difference"], 2)}}</span>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column ">
                                                <div class="d-flex gap-1 align-items-center">
                                                    <span class="country-code">{{ $data["countryCode"]}}</span>
                                                    <span class="country-code-rate">{{ number_format($subarea["scores"]["country"], 2)}}</span>
                                                </div>
                                                <div class="d-flex gap-1 align-items-center">
                                                    <span class="eu-code">EU</span>
                                                    <span class="eu-code-rate">{{ number_format($subarea["scores"]["euAverage"], 2)}}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="codes-wrapper"> <p> {{$subarea["name"]}}</p></div>
                                        <span class="subarea-desc">{{$subarea["description"]}}</span>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="col-12 col-sm-7 offset-xs-1 p-0">
                                    <div id="radar-area-{{ $loop->index }}" style="width: 100%; height: 300px;"></div>
                                </div>
                            
                            @endif
                        </div>
                    </div>
                           
                   @endforeach
                </div>
            </section>

            <section  id="excellence-table-grid">
                {{-- first table --}}
                <div class="col-12">
                    <h3 class="sub-heading">Top-performing domains</h3>
                    <p>This section shows the country’s top-performing domains. These correspond to the five indicators for which a country has a higher ranking (between 1st and 5th position) with respect to other countries. For example, a country that ranks first for an indicator will have that indicator shown in the list of top-performing domains
                        <sup>(7)</sup>.</p>
                </div>
                <div class="col-12 page-break-after">
                    <div class="table-container">
                        <div class="table-header table-row">
                          <div class="table-cell"><span>Sub-areas</span></div>
                          <div class="table-cell top-ms-indicator"><span>Indicator</span></div>
                          <div class="table-cell top-ms-algorithm"><span>Algorithm</span></div>
                          <div class="table-cell justify-content-center"><span>Scores</span></div>
                          <div class="table-cell"><span>Positioning</span></div>
                        </div>
    
                        <div class="table-body table-row">
                          @foreach ($data['domains_of_excellence'] as $excellence)
                             <div class="table-cell">
                                <div class="d-flex flex-column gap-2 justify-conten">
                                    <span class="domains-label">{{$excellence['areaName']}}</span>
                                    <p class="domains-main">{{$excellence['subareaName']}}</p>
                                    <span class="font-weight-300">{{$excellence['subareaDescription']}}</span>
                                </div>
                             </div>
                             <div class="table-cell top-ms-indicator">
                                <div class="d-flex flex-column justify-content-center align-items-center h-100 gap-3">
                                    <span class="text-center font-weight-700">{{$excellence['indicator']}}</span>
                                    <div class="d-flex flex-column gap-2">
                                        <span class="text-center classWeight">{{$excellence['weight']}}</span>
                                        <span class="text-center classMS"> {{$excellence['source']}}&nbsp;({{$excellence['referenceYear']}})</span>
                                    </div>
                                </div>
                             </div>
                             <div class="table-cell top-ms-algorithm">
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <span class="font-weight-300 algorithm">{!! nl2br($excellence['algorithm']) !!}</span>
                                </div>
                             </div>
                             <div class="table-cell">
                                <div class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <span class="scoresMS font-weight-700">{{number_format($excellence['countryScore'], 2)}}</span>
                                    <span class="scoresEU font-weight-700">{{number_format($excellence['euAverage'], 2)}}</span>
                                    <br>
                                    <span>
                                        <strong class="text-center">What does 100 mean</strong>
                                    </span>
                                    <span class="algorithm text-center">{{$excellence['w100means']}}</span>
                                </div>
                             </div>
                            <div class="table-cell">
                                <div class="d-flex flex-column gap-2 justify-content-center align-items-center h-100">
                                    <span class="speedometer-{{$excellence['speedometer']}}"><span class="speed-range">{{$excellence['difference']}}</span></span>
                                </div>
                            </div>
                          @endforeach
                        </div>                        
                    </div>
                </div>

                {{-- second table --}}
                <div class="col-12  mt-3">
                    <p>The table below shows the country’s top five indicators in terms of difference from the EU average i.e. the first indicator is the indicator with the largest positive difference from the EU average.</p>
                </div>
                <div class="col-12 page-break-after">
                    <div class="table-container">
                        <div class="table-header table-row">
                          <div class="table-cell"><span>Sub-areas</span></div>
                          <div class="table-cell top-ms-indicator"><span>Indicator</span></div>
                          <div class="table-cell top-ms-algorithm"><span>Algorithm</span></div>
                          <div class="table-cell justify-content-center"><span>Scores</span></div>
                          <div class="table-cell"><span>Positioning</span></div>
                        </div>                             
                        <div class="table-body table-row">
                          @foreach ($data['domains_of_excellence_diff'] as $excellence)
                             <div class="table-cell">
                                <div class="d-flex flex-column gap-2 justify-conten">
                                    <span class="domains-label">{{$excellence['areaName']}}</span>
                                    <p class="domains-main">{{$excellence['subareaName']}}</p>
                                    <span class="font-weight-300">{{$excellence['subareaDescription']}}</span>
                                </div>
                             </div>
                             <div class="table-cell top-ms-indicator">
                                <div class="d-flex flex-column justify-content-center align-items-center h-100 gap-3">
                                    <span class="text-center font-weight-700">{{$excellence['indicator']}}</span>
                                    <div class="d-flex flex-column gap-2">
                                        <span class="text-center classWeight">{{$excellence['weight']}}<br></span>
                                        <span class="text-center classMS"> {{$excellence['source']}}&nbsp;({{$excellence['referenceYear']}})</span>
                                    </div>
                                </div>
                             </div>
                             <div class="table-cell top-ms-algorithm">
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <span class="font-weight-300 algorithm">{!! nl2br($excellence['algorithm']) !!}</span>
                                </div>
                             </div>
                             <div class="table-cell">
                                <div class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <span class="scoresMS font-weight-700">{{number_format($excellence['countryScore'], 2)}}</span>
                                    <span class="scoresEU font-weight-700">{{number_format($excellence['euAverage'], 2)}}</span>
                                     <br>
                                    <span>
                                        <strong class="text-center">What does 100 mean</strong>
                                    </span>
                                    <span class="algorithm text-center">{{$excellence['w100means']}}</span>
                                </div>
                             </div>
                            <div class="table-cell">
                                <div class="d-flex flex-column gap-2 justify-content-center align-items-center h-100">
                                    <span class="speedometer-{{$excellence['speedometer']}}"><span class="speed-range">{{$excellence['difference']}}</span></span>
                                </div>
                            </div>
                          @endforeach
                        </div>                        
                    </div>
                    <div class="mt-3">
                        <!-- <span class="footnote">If a country has more than 5 indicators with a higher ranking (for example, a country might rank first in more than 5 indicators), the tie is broken first by selecting the indicator with the highest weight and, if the tie is still unbroken, by selecting the indicator with the highest positive difference from the EU average.
                        </span> -->
                    </div>
                </div>
            </section>

            <section id="improvement-table-grid">
                <div class="col-12  ">
                    <h3 class="sub-heading">Least-performing domains</h3>
                    <p>This section shows the country’s least-performing cybersecurity domains. These are the five indicators for which a country has a lower ranking (between 21st and 27th position) with respect to other countries. For example, a country that ranks last for an indicator will have that indicator shown in the list of least-performing domains.<sup>(8)</sup></p>
                </div>
                {{-- first table --}}
                <div class="col-12 page-break-after">
                    <div class="table-container">
                        <div class="table-header table-row">
                          <div class="table-cell"><span>Sub-areas</span></div>
                          <div class="table-cell top-ms-indicator"><span>Indicator</span></div>
                          <div class="table-cell top-ms-algorithm"><span>Algorithm</span></div>
                          <div class="table-cell justify-content-center"><span>Scores</span></div>
                          <div class="table-cell"><span>Positioning</span></div>
                        </div>
    
                        <div class="table-body table-row">
                            @foreach ($data['domains_of_improvement'] as $improvement)
                             <div class="table-cell">
                                <div class="d-flex flex-column gap-2">
                                    <span class="domains-label">{{$improvement['areaName']}}</span>
                                    <p class="domains-main">{{$improvement['subareaName']}}</p>
                                    <span class="font-weight-300">{{$improvement['subareaDescription']}}</span>
                                </div>
                             </div>
                             <div class="table-cell top-ms-indicator">
                                <div class="d-flex flex-column justify-content-center align-items-center h-100 gap-3">
                                    <span class="text-center font-weight-700">{{$improvement['indicator']}}</span>
                                    <div class="d-flex flex-column gap-2">
                                        <span class="text-center classWeight">{{$improvement['weight']}}<br></span>
                                        <span class="text-center classMS"> {{$improvement['source']}}&nbsp;({{$improvement['referenceYear']}})</span>
                                    </div>
                                </div>
                             </div>
                             <div class="table-cell top-ms-algorithm">
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <span class="font-weight-300 algorithm">{!! nl2br($improvement['algorithm']) !!}</span>
                                </div>
                             </div>
                             <div class="table-cell">
                                <div class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <span class="scoresMS font-weight-700">{{number_format($improvement['countryScore'], 2)}}</span>
                                    <span class="scoresEU font-weight-700">{{number_format($improvement['euAverage'], 2)}}</span>
                                    <br>
                                    <span>
                                        <strong class="text-center">What does 100 mean</strong>
                                    </span>
                                    <span class="algorithm text-center">{{$improvement['w100means']}}</span>
                                </div>
                             </div>
                            <div class="table-cell">
                                <div class="d-flex flex-column gap-2 justify-content-center align-items-center h-100">
                                    <span class="speedometer-{{$improvement['speedometer']}}"><span class="speed-range">{{$improvement['difference']}}</span></span>
                                </div>
                            </div>
                          @endforeach
                        </div>                        
                    </div>
                </div>
                <div class="col-12  mt-3">
                    <p>The table below shows the country’s least five indicators in terms of difference from the EU average i.e. the first indicator is the indicator with the largest negative difference from the EU average.</p>
                </div>
                {{-- second table --}}
                <div class="col-12 page-break-after">
                    <div class="table-container">
                        <div class="table-header table-row">
                        <div class="table-cell"><span>Sub-areas</span></div>
                        <div class="table-cell top-ms-indicator"><span>Indicator</span></div>
                        <div class="table-cell top-ms-algorithm"><span>Algorithm</span></div>
                        <div class="table-cell justify-content-center"><span>Scores</span></div>
                        <div class="table-cell"><span>Positioning</span></div>
                        </div>                              

                        <div class="table-body table-row">
                            @foreach ($data['domains_of_improvement_diff'] as $improvement)
                            <div class="table-cell">
                                <div class="d-flex flex-column gap-2">
                                    <span class="domains-label">{{$improvement['areaName']}}</span>
                                    <p class="domains-main">{{$improvement['subareaName']}}</p>
                                    <span class="font-weight-300">{{$improvement['subareaDescription']}}</span>
                                </div>
                            </div>
                            <div class="table-cell top-ms-indicator">
                                <div class="d-flex flex-column justify-content-center align-items-center h-100 gap-3">
                                    <span class="text-center font-weight-700">{{$improvement['indicator']}}</span>
                                    <div class="d-flex flex-column gap-2">
                                        <span class="text-center classWeight">{{$improvement['weight']}}<br></span>
                                        <span class="text-center classMS"> {{$improvement['source']}}&nbsp;({{$improvement['referenceYear']}})</span>
                                    </div>
                                </div>
                            </div>
                            <div class="table-cell top-ms-algorithm">
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <span class="font-weight-300 algorithm">{!! nl2br($improvement['algorithm']) !!}</span>
                                </div>
                            </div>
                            <div class="table-cell">
                                <div class="d-flex flex-column justify-content-center align-items-center h-100">
                                    <span class="scoresMS font-weight-700">{{number_format($improvement['countryScore'], 2)}}</span>
                                    <span class="scoresEU font-weight-700">{{number_format($improvement['euAverage'], 2)}}</span>
                                    <br>
                                    <span>
                                        <strong class="text-center">What does 100 mean</strong>
                                    </span>
                                    <span class="algorithm text-center">{{$improvement['w100means']}}</span>
                                </div>
                            </div>
                            <div class="table-cell">
                                <div class="d-flex flex-column gap-2 justify-content-center align-items-center h-100">
                                    <span class="speedometer-{{$improvement['speedometer']}}"><span class="speed-range">{{$improvement['difference']}}</span></span>
                                </div>
                            </div>
                        @endforeach
                        </div>                    
                    </div>
                </div>
            </section>

            <section id="annex-table-grid" class="row page-break-after">
                <div class="col-12">
                    <h3 class="sub-heading">Annex</h3>
                    <p>The annex shows the index values for the complete list of indicators and the performance of the country with respect to the EU average.</p>
                </div>
                <div class="col-12">
                    @foreach ($data['allIndicators'] as $annex)
                        @foreach( $annex['areas'] as $annex_area)
                            @foreach ( $annex_area['subareas'] as $annex_subarea)
                                    <div class="annex-wrap page-break-after">
                                        <div class="d-flex flex-column dark">
                                            <span class="ps-2 pt-1 subtext">AREA</span>
                                            <p class="annex-area m-0 ps-2 pb-1 font-weight-800">
                                                {{$annex_area["name"]}}
                                            </p>
                                        </div>
                                        <div class="d-flex flex-column grey">
                                            <span class="ps-2 pt-1 subtext">SUBAREA</span>
                                            <p class="annex-area m-0 ps-2 font-weight-700">
                                                    {{$annex_subarea["name"]}} 
                                            </p>
                                            <span class="ps-2 pb-1">{{$annex_subarea["description"]}}</span>
                                        </div>
                                        <div class="table-container">

                                            <div class="table-header table-row">                                          
                                                <div class="table-cell annex-indicator-col"><span>Indicator</span></div>
                                                <div class="table-cell annex-algorithm-col"><span>Algorithm</span></div>
                                                <div class="table-cell annex-score-col justify-content-center"><span>Score</span></div>
                                                <div class="table-cell"><span>Perfomance</span></div>
                                            </div>  
                                            @foreach ( $annex_subarea['indicators'] as $annex_indicator)
                                                <div class="table-body table-row">
                                                    <div class="table-cell annex-indicator-col">
                                                        <div class="d-flex flex-column justify-content-center align-items-center h-100 gap-3">
                                                            <span class="text-center font-weight-700">{{$annex_indicator['indicator']}}{!! GeneralHelper::showDisclaimers($annex_indicator['dislcaimers']) !!}</span>
                                                            <div class="d-flex flex-column gap-2">
                                                                <span class="text-center classWeight">{{$annex_indicator['weight']}}<br></span>
                                                                <span class="text-center classMS"> {{$annex_indicator['source']}}&nbsp;({{$annex_indicator['referenceYear']}})</span>
                                                            </div>
                                                        </div>         
                                                    </div>
                                                    <div class="table-cell annex-algorithm-col">
                                                        <div class="d-flex justify-content-center align-items-center h-100" >
                                                            <span class="font-weight-300 algorithm-eu-annex">{!! nl2br($annex_indicator['algorithm']) !!}</span>
                                                        </div>         
                                                    </div>
                                                    <div class="table-cell annex-score-col">  
                                                        <div class="d-flex flex-column justify-content-center align-items-center h-100">
                                                            <span class="scoresMS font-weight-700">{{number_format($annex_indicator['countryScore'], 2)}}</span>
                                                            <span class="scoresEU font-weight-700">{{number_format($annex_indicator['euAverage'], 2)}}</span>
                                                            <br>
                                                            <span>
                                                                <strong class="text-center">What does 100 mean</strong>
                                                            </span>
                                                            <span class="algorithm text-center">{{$annex_indicator['w100means']}}</span>
                                                        </div>       
                                                    </div>
                                                    <div class="table-cell">
                                                        <div class="d-flex flex-column gap-2 justify-content-center align-items-center h-100">
                                                            <span class="speedometer-{{$annex_indicator['speedometer']}}"><span class="speed-range">{{$annex_indicator['difference']}}</span></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                            @endforeach
                        @endforeach
                   @endforeach
                </div>
            </section>
            
            <section id="disclaimers-table-grid">
                    <h3>Disclaimers</h3>
                <div class="col-12">
                    <div class="table-container">

                        <div class="table-header table-row">
                            <div class="table-cell"><span>#</span></div>
                            <div class="table-cell"><span>Data disclaimers</span></div>
                        </div>
                        
                        <div class="table-body table-row">
                            <div class="table-cell">
                                <span>1</span>
                            </div>
                            <div class="table-cell">
                                <span>Normalised using fraction to the maximum.</span>
                           </div>
                       </div>
                       <div class="table-body table-row">
                           <div class="table-cell">
                                <span>2</span>
                           </div>
                           <div class="table-cell">
                            <span>Normalised using ranking.</span>
                          </div>
                      </div>
                      <div class="table-body table-row">
                            <div class="table-cell">
                                <span>3</span>
                            </div>
                            <div class="table-cell">
                                <span>Normalised using goalposts with targets (0, 100).</span>
                           </div>
                       </div>
                       <div class="table-body table-row">
                           <div class="table-cell">
                                <span>4</span>
                           </div>
                           <div class="table-cell">
                                <span>Normalised using goalposts with targets (0, 75).</span>
                          </div>
                      </div>
                      <div class="table-body table-row">
                            <div class="table-cell">
                                <span>5</span>
                            </div>
                            <div class="table-cell">
                                <span><strong>Imputed with median:</strong> data collected from external sources not available, hence the data shown is the median of the rest of the available data for the indicator.</span>
                            </div>
                       </div>                
                       <div class="table-body table-row">
                           <div class="table-cell">
                                <span>6</span>
                           </div>
                           <div class="table-cell">
                               <span><strong>Imputed with average score of questions answered, rounded down to the lowest possible answered score:</strong> the Member State replied to at least one question for this indicator "Data not available/Not willing to share".</span>
                           </div>
                      </div>
                      <div class="table-body table-row">
                            <div class="table-cell">
                                <span>7</span>
                            </div>
                            <div class="table-cell">
                                <span><strong>Treated outlier:</strong> the original data point was an outlier. The data shown is the next closest value within the data distribution. For example, if the data distribution is between 10% and 20%, a 40% outlier would be shown as 20%.</span>         
                            </div>
                       </div>
                    </div>
                </div>
            </section>

        </main>

<style>
 @media (min-width: 769px) {
        .target-area.col-sm-3:nth-of-type(1),
        .target-area.col-sm-3:nth-of-type(2),
        .target-area.col-sm-3:nth-of-type(3) {
            border-right: 1px solid #C6C6C6;
        }
}

</style>
<link rel="stylesheet" href="{{ mix('css/app.css') }}">

<script src="{{ mix('mix/js/paged.polyfill.js') }}"></script>
<script>
    class MyHandler extends Paged.Handler {
     constructor(chunker, polisher, caller) {
       super(chunker, polisher, caller);
     }
  
     afterRendered(pages) {
       getChartData();
     }
   }
   Paged.registerHandlers(MyHandler);
  
  function getChartData()
  {
      let index = $('#index').val();
  
      $.ajax({
          url: '/index/report/chartData/' + index,
          success: function (data) {
              initializeRadarChart('radar-area-0', data['area-0']);
              initializeRadarChart('radar-area-1', data['area-1']);
              initializeRadarChart('radar-area-2', data['area-2']);
              initializeRadarChart('radar-area-3', data['area-3']);
             const button = parent.document.getElementById('export-pdf');
             setTimeout(() => {
                button.disabled = false;
                button.textContent = 'Export | PDF';
             }, 1000);
             
          }
      });
  }
  
  function initializeRadarChart(element, data)
  {
      var chartDom = document.getElementById(element);
      var myChart = echarts.init(chartDom, null, {
          renderer: 'svg'
      });
     
      var option;
  
      option = {
          color: ['#CE0F3E','#004F9F', '#8B8B8E'],
          textStyle: {
              color: "rgba(20,20,20, 0.6)",
              fontSize: 7,
          },
          radar: {
              shape: 'circle',
              textStyle: {
                  color: '#141414',
                  fontSize: 7,
              },
              name: {
              textStyle: {
                  color: '#141414',
                  fontSize: 7,
              }
          },
              indicator: data.indicator.map(indicator => ({
                  name: indicator.name.length > 44 ? indicator.name.substring(0, 44) + '...' : indicator.name,
                  max: indicator.max
              })),
          },
          legend: {
              show: true,
              orient: 'vertical',
              left: 'right',
              top: 'top',
              data: [
                  {name: data.name, icon: 'circle', itemStyle: {color: '#CE0F3E' } },
                  {name: 'EU', icon: 'circle', itemStyle: {color: '#004F9F' } },
                  {name: 'max values', icon: 'circle', itemStyle: {color: '#8B8B8E' } }
              ],
              selectedMode: false
          },
          series: [
              {
                  name: 'EU',
                  type: 'radar',
                  data: [
                      {
                          value: data.country,
                          name: data.name
                      },
                      {
                          value: data.eu,
                          name: 'EU'
                      },
                      {
                          value: 100,
                          name: 'max values',
                          lineStyle: {
                              type: 'dashed'
                          },
                      }
                  ]
              }
          ]
      };
  
      option && myChart.setOption(option);
  }
  
  </script>
@endsection