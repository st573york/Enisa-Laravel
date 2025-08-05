@extends('layouts.app')

@section('title', 'Index')

@section('content')
<main id="enisa-main" class="bg-white">
    <div class="container-fluid">

        <div class="row ps-0">
            <div class="col-10 offset-1 ps-0">
                <div class="enisa-breadcrump d-flex">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb text-end">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active"><a href="#">Index - Visualisations</a></li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

        @include('components.alert', ['type' => 'pageAlert'])

        @php
            $is_admin = Auth::user()->isAdmin();
        @endphp

        <div class="row mt-3">
            <div class="col-10 offset-1 ps-0 pe-0">
                <div class="table-section col-12 mt-2">
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <div>
                                    <h2 class="mt-2">{{ __('Year') }} -</h2>
                                </div>
                                <div class="mt-1">
                                    @include('components.year-dropdown', ['years' => $years])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="comparison-data"></div>

        <div class="modal" tabindex="-1" id="indicator-modal">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="row">
                            <div class="col-12" id="algorithm-container"></div>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-enisa" data-bs-dismiss="modal">
                            {{ __('OK') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<script src="{{ mix('mix/js/main.js') }}" defer></script>
<script src="{{ mix('mix/js/alert.js') }}" defer></script>

<script>
    let pageModal = new bootstrap.Modal(document.getElementById('indicator-modal'));
    let mapChart;
    let comparisonLoaded = false;
    let sunburstLoaded = false;
    let is_admin = "{{ $is_admin }}";

    $(document).ready(function() {
        skipFadeOut = true;

        loadData();
    });

    function loadData()
    {
        comparisonLoaded = false;
        sunburstLoaded = false;

        let year = getIndexYear();

        $.ajax({
            url: '/index/access/' + year,
            success: function(data) {
                let has_data = (data.indexOf('no-data') < 0);
                let has_reports_visualisations = (data.indexOf('no-reports-visualisations') < 0);

                if (!has_data ||
                    !has_reports_visualisations)
                {
                    $('.comparison-data').html('');

                    $('.loader').fadeOut();

                    let msg = 'No data available. Data collection for ' + year + ' is currently in progress.';
                    if (is_admin &&
                        has_data &&
                        !has_reports_visualisations)
                    {
                        msg = 'EU/MS Reports/Visualisations for ' + year + ' are unpublished.\
                            Browse to <a href="/index/show/' + $('#index-year-select option:selected').attr('data-id') + '">Index</a> to publish them.';
                    }
                    
                    setAlert({
                        'status': 'warning',
                        'msg': msg
                    });

                    localStorage.removeItem('index-country');
                }
                else {
                    $('.comparison-data').html(data);
                }
            }
        });
    }

    function checkCountry()
    {
        let countryIndex = getIndexCountry();
        
        if (countryIndex) {
            setTimeout(() => {
                loadCountry(countryIndex);
            }, 1000);
        }
        else {
            $('.loader').fadeOut();
        }
    }

    function loadCountry(countryIndex)
    {
        mapChart.dispatchAction({
            type: 'select',
            dataIndex: countryIndex
        });

        localStorage.removeItem('index-country');

        $('.loader').fadeOut();
    }

    window.addEventListener('yearChange', function() {
        $('.loader').fadeIn();

        loadData();
    });

    window.addEventListener('comparisonLoaded', function() {
        comparisonLoaded = true;
        
        if (sunburstLoaded) {
            checkCountry();
        }
    });

    window.addEventListener('sunburstLoaded', function() {
        sunburstLoaded = true;
        
        if (comparisonLoaded) {
            checkCountry();
        }
    });
</script>
@endsection
