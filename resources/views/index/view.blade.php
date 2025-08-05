@extends('layouts.app')

@section('title', 'Index')

@section('content')

    <main id="enisa-main" class="bg-white">
        
        <div class="container-fluid">
            <div class="row ">
                <div class="col-10 offset-1 ps-0">
                    <div class="enisa-breadcrump p-1 d-flex">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb text-end">
                                <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                                <li class="breadcrumb-item"><a dusk="indexes_breadcrumb" href="/index/management">{{ __('Indexes') }}</a></li>
                                <li class="breadcrumb-item active"><a href="#">{{ __('Edit Index') }}</a>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-5 offset-1 ps-0">
                    <h1 dusk="index_title">{{ __('Edit Index') }}</h1>
                </div>
                <div class="col-lg-5 col-11">
                    <div class="d-flex gap-2 justify-content-end">
                        <button dusk="save_index" id="save-changes" class="btn btn-enisa">{{ __('Save changes') }}</button>
                        <button dusk="delete_index" id="delete-index" class="btn btn-enisa-invert" {{ ($index->draft) ? '' : 'disabled' }}>{{ __('Delete') }}</button>
                    </div>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert', 'padding' => 'ps-0 pe-0'])

            <div class="row mt-5">
                <div class="col-10 offset-1 ps-0 pe-0">
                    <div class="row">
                        <div class="table-section col-12 mt-2">
                            <form dusk="edit_index_data" class="row">
                                <input hidden name="id" id="index-id" value="{{ $index->id }}" />
                                <div class="input-group mb-3 required">
                                    <label for="indexName" class="col-form-label col-sm-3">Name:</label>
                                    <div class="col-sm-9">
                                        <input dusk="edit_index_name" type="text" name="name" class="form-control" id="indexName"
                                            {{ $index->draft ? '' : 'disabled' }} value="{!! $index->name !!}">
                                        <div dusk="edit_index_name_invalid" class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="input-group mb-3">
                                    <label for="indexDescription" class="col-form-label col-sm-3">Description:</label>
                                    <div class="col-sm-9">
                                        <input dusk="edit_index_description" type="text" name="description" class="form-control" id="indexDescription"
                                            {{ $index->draft ? '' : 'disabled' }} value="{!! $index->description !!}">
                                    </div>
                                </div>
                                <div class="input-group mb-3 required">
                                    <label for="indexYear" class="col-form-label col-sm-3">Year:</label>
                                    <div class="col-sm-9">
                                        <select dusk="edit_index_year"  {{ $index->draft ? '' : 'disabled' }} id="indexYear" name="year" class="form-select">
                                            <option value="" selected disabled>{{ __('Choose...') }}</option>
                                            @foreach ($years as $year)
                                                <option {{ $index->year == $year ? 'selected' : '' }}
                                                    value="{{ $year }}">{{ $year }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div dusk="edit_index_year_invalid" class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="input-group mb-3">
                                    <div class="col-sm-3">
                                        <span>Status:</span>
                                    </div>
                                    <div class="col-sm-9">
                                        <div class="d-flex gap-2 labels-col">
                                            @php
                                                $dusk_status_switch = 'dusk=edit_index_status_switch';
                                                $dusk_status_text = 'dusk=edit_index_status_text';
                                            @endphp
                                            @if ($index->draft)
                                                <div class="d-flex gap-2">
                                                    <div class="form-check form-switch">
                                                        <input {{ $dusk_status_switch }} type="checkbox"
                                                            class="form-check-input switch unchecked-style switch-positive" name="draft" id="blockSwitch">
                                                    </div>
                                                    <span {{ $dusk_status_text }} class="label pt-1">Unpublished</span>
                                                </div>
                                            @else
                                                <div class="d-flex gap-2">
                                                    <div class="form-check form-switch">
                                                        <input {{ $dusk_status_switch }} type="checkbox"
                                                            class="form-check-input switch switch-positive {{ ($index->index()->count() && $index->baseline()->count()) ? 'switch-deactivated' : '' }}" name="draft" id="blockSwitch" checked>
                                                    </div>
                                                    <span {{ $dusk_status_text }} class="label pt-1">Published</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="input-group mb-3">
                                    <div class="col-sm-3">
                                        <span>EU Reports/Visualisations:</span>
                                    </div>
                                    <div class="col-sm-9">
                                        <div class="d-flex gap-2 labels-col">
                                            @php
                                                $dusk_eu_switch = 'dusk=edit_index_eu_switch';
                                                $dusk_eu_text = 'dusk=edit_index_eu_text';
                                            @endphp
                                            @if ($index->eu_published)
                                                <div class="d-flex gap-2">
                                                    <div class="form-check form-switch">
                                                        <input {{ $dusk_eu_switch }} type="checkbox"
                                                            class="form-check-input switch switch-positive {{ ($index->ms_published) ? 'switch-deactivated' : '' }}" name="eu_published" id="euSwitch" checked>
                                                    </div>
                                                    <span {{ $dusk_eu_text }} class="label pt-1">Published</span>
                                                </div>
                                            @else
                                                <div class="d-flex gap-2">
                                                    <div class="form-check form-switch">
                                                        <input {{ $dusk_eu_switch }} type="checkbox"
                                                            class="form-check-input switch unchecked-style switch-positive" name="eu_published" id="euSwitch">
                                                    </div>
                                                    <span {{ $dusk_eu_text }} class="label pt-1">Unpublished</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <div class="col-sm-3">
                                        <span>MS Reports/Visualisations:</span>
                                    </div>
                                    <div class="col-sm-9">
                                        <div class="d-flex gap-2 labels-col">
                                            @php
                                                $dusk_ms_switch = 'dusk=edit_index_ms_switch';
                                                $dusk_ms_text = 'dusk=edit_index_ms_text';
                                            @endphp
                                            @if ($index->ms_published)
                                                <div class="d-flex gap-2">
                                                    <div class="form-check form-switch">
                                                        <input {{ $dusk_ms_switch }} type="checkbox"
                                                            class="form-check-input switch switch-positive" name="ms_published" id="msSwitch" checked>
                                                    </div>
                                                    <span {{ $dusk_ms_text }} class="label pt-1">Published</span>
                                                </div>
                                            @else
                                                <div class="d-flex gap-2">
                                                    <div class="form-check form-switch">
                                                        <input {{ $dusk_ms_switch }} type="checkbox"
                                                            class="form-check-input switch unchecked-style switch-positive {{ (!$index->eu_published) ? 'switch-deactivated' : '' }}" name="ms_published" id="msSwitch">
                                                    </div>
                                                    <span {{ $dusk_ms_text }} class="label pt-1">Unpublished</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-5">
                <div class="col-10 offset-1 ps-0 pe-0">
                    <div class="row">

                        <div class="table-section col-12 col-lg-7 mt-2">
                            <div class="d-flex flex-column gap-2">
                                <h2 class="mb-2"><span>{!! $index->name !!}</span></h2>
                            </div>
                            <table id="area-table" class="display enisa-table-group">
                                <thead>
                                    <tr>
                                        <th>{{ __('Areas') }}</th>
                                        <th>{{ __('Weight') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>

                        <div class="table-section col-12 col-lg-7 mt-2 hidden">
                            <div class="d-flex justify-content-between">
                                <h2 class="mb-2"><span class="area-name"></span></h2>
                                <ol class="index-nav breadcrumb text-end">
                                    <li class="breadcrumb-item index-edit">{!! $index->name !!}</li>
                                    <li class="breadcrumb-item area-edit active">
                                        <span class="area-name"></span>
                                    </li>
                                </ol>
                            </div>
                            <table id="subarea-table" class="display enisa-table-group">
                                <thead>
                                    <tr>
                                        <th>{{ __('Subreas') }}</th>
                                        <th>{{ __('Weight') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>

                        <div class="table-section col-12 col-lg-7 mt-2 hidden">
                            <div class="d-flex justify-content-between">
                                <h2 class="mb-2"><span class="subarea-name"></span></h2>
                                <ol class="index-nav breadcrumb text-end">
                                    <li class="breadcrumb-item index-edit">{!! $index->name !!}</li>
                                    <li class="breadcrumb-item area-edit">
                                        <span class="area-name"></span>
                                    </li>
                                    <li class="breadcrumb-item subarea-edit active">
                                        <span class="subarea-name"></span>
                                    </li>
                                </ol>
                            </div>
                            <table id="indicator-table" class="display enisa-table-group">
                                <thead>
                                    <tr>
                                        <th>{{ __('Indicators') }}</th>
                                        <th>{{ __('Weight') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                        <div class="table-section col-12 conf-tree-col mt-2"></div>

                    </div>
                </div>
            </div>
        </div>
        <div dusk="edit_index_modal" class="modal fade" id="pageModal" tabindex="-1" aria-labelledby="pageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header p-3">
                        <h5 dusk="edit_index_modal_title" class="modal-title" id="pageModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div dusk="edit_index_modal_text" class="modal-body p-3"></div>
                    <div class="modal-footer justify-content-between">
                        <button dusk="edit_index_modal_close" type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button dusk="edit_index_modal_process" type="button" class="btn btn-enisa process-data" id='process-data'></button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/edit.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>
    <script src="{{ mix('mix/js/modal.js') }}" defer></script>

    <script>
        var pageModal = new bootstrap.Modal(document.getElementById('pageModal'));
        let indexId = {{ $index->id }};
        let indexName = "{{ $index->name }}";
        let tableData;
        let indexJson;
        let areaId = null;
        let subareaId = null;
        let areas;
        let subareas;
        let indicators;
        let usedAreaCount = 0;
        let usedSubareaCount = 0;
        let usedIndicatorCount = 0;
        let currentTable;
        let currentElement;
        let indexOfficial = !{{ $index->draft }};

        $(document).ready(function() {
            skipFadeOut = true;
        });

        function nodeData(element)
        {
            tableData = [];
            
            if (indexJson.contents != undefined)
            {
                for (const [key, area] of Object.entries(indexJson.contents))
                {
                    if (!areaId) {
                        tableData.push(area.area);
                    }
                    else if (area.area.id == areaId)
                    {
                        if (!subareaId) {
                            tableData = area.area.subareas;
                        }
                        else
                        {
                            for (const [key, subarea] of Object.entries(area.area.subareas))
                            {
                                if (subarea.id == subareaId)
                                {
                                    tableData = subarea.indicators;

                                    break;
                                }
                            }
                        }

                        break;
                    }
                }
            }

            drawTable(element);
        }

        function drawTable(element)
        {
            currentElement = element;
            
            if ($.fn.DataTable.isDataTable(element))
            {
                $(element).DataTable().clear();
                $(element).DataTable().destroy();
            }

            let component = element.replace('#', '').replace('-table', '');

            currentTable = $(element).DataTable({
                "drawCallback": function() {
                    toggleDataTablePagination(this, element + '_paginate');
                },
                "data": tableData,
                "columns":
                [
                    {
                        "data": "name",
                        render: function(data, type, row) {
                            return '<span class="' + component + '-edit" data-id=' + row.id + '>' + data + '</span>';
                        }
                    },
                    {
                        "data": "weight"
                    }
                ]
            });
        }

        $(document).on('change', '#blockSwitch', function() {
            $(this).toggleClass('unchecked-style').parent().siblings('.label');
            $(this).checked = !$(this).checked;

            if ($(this).hasClass('unchecked-style')) {
                $(this).parent().siblings('.label').text('Unpublished');
            }
            else {
                $(this).parent().siblings('.label').text('Published');
            }
        });

        $(document).on('change', '#euSwitch', function() {
            $(this).toggleClass('unchecked-style').parent().siblings('.label');
            $(this).checked = !$(this).checked;

            if ($(this).hasClass('unchecked-style'))
            {
                $(this).parent().siblings('.label').text('Unpublished');

                $('#msSwitch').addClass('switch-deactivated');
            }
            else
            {
                $(this).parent().siblings('.label').text('Published');

                $('#msSwitch').removeClass('switch-deactivated');
            }
        });

        $(document).on('change', '#msSwitch', function() {
            $(this).toggleClass('unchecked-style').parent().siblings('.label');
            $(this).checked = !$(this).checked;

            if ($(this).hasClass('unchecked-style'))
            {
                $(this).parent().siblings('.label').text('Unpublished');

                $('#euSwitch').removeClass('switch-deactivated');
            }
            else
            {
                $(this).parent().siblings('.label').text('Published');

                $('#euSwitch').addClass('switch-deactivated');
            }
        });
    </script>
@endsection
