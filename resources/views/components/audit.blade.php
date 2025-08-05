@extends('layouts.app')

@section('title', 'Index')

@section('content')

    <main id="enisa-main" class="bg-white">
        <div class="container-fluid">

            <div class="row ">
                <div class="col-10 offset-1 ps-0">
                    <div class="enisa-breadcrump d-flex">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb text-end">
                                <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                                <li class="breadcrumb-item active"><a href="#">{{ __('Auditing') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row ">
                <div class="col-10 offset-1 ps-0">
                    <h1 class="indicators-title">{{ __('Auditing') }}</h1>
                </div>
            </div>

            <div class="row">
                <div class="col-10 offset-1 ps-0 pe-0">
                    <div class="table-section col-12 mt-2">
                        <div class="d-flex gap-4 mb-4">
                            <div>
                                <label for="minDate">Date From:</label>
                                <div>
                                    <div class="input-group date">
                                        <input type="text" class="form-control datepicker" id="minDate" value="{{ $dateToday }}"/>
                                        <span class="input-group-append"></span>
                                        <span class="icon-calendar"> </span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="maxDate">Date To:</label>
                                <div>
                                    <div class="input-group date">
                                        <input type="text" class="form-control datepicker" id="maxDate" value="{{ $dateToday }}"/>
                                        <span class="input-group-append"></span>
                                        <span class="icon-calendar"> </span>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="model">Model:</label>
                                <select id="model" class="form-select" aria-label="Select Model">
                                    <option value="All" selected>{{ __('All') }}</option>
                                    @foreach ($models as $model)
                                        <option value="{{ $model }}">{{ $model }}</option>
                                    @endforeach
                                  </select>
                            </div>
                            <div>
                                <label for="event">Event:</label>
                                <select id="event" class="form-select" aria-label="Select Event">
                                    <option value="All" selected>{{ __('All') }}</option>
                                    @foreach ($events as $event)
                                        <option value="{{ $event }}">{{ str_replace('( ', '(', ucwords(str_replace('(', '( ', $event))) }}</option>
                                    @endforeach
                                  </select>
                            </div>
                        </div>
                        <table id="audit-table" class="display enisa-table-group">
                            <thead>
                                <tr>
                                    <th>{{ __('Id') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('IP Address') }}</th>
                                    <th>{{ __('Event') }}</th>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Affected Entity') }}</th>
                                    <th>{{ __('Changes') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="pageModal" tabindex="-1" aria-labelledby="pageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header p-3">
                        <h5 class="modal-title" id="pageModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3"></div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    </div>
                </div>
            </div>
        </div> 
    </main>

    <script src="{{ mix('mix/js/datepicker-custom.js') }}" defer></script>
    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/modal.js') }}" defer></script>

    <script>
        var pageModal = new bootstrap.Modal(document.getElementById('pageModal'));
        let auditTable;
        let auditChangesTable;
        let section = '/audit';
        let span = document.createElement('span');

        $(document).ready(function() {
            initDatePicker();

            auditTable = $('#audit-table').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": section + '/list',
                    "data": function (data) {
                        data.minDate = $('#minDate').val();
                        data.maxDate = $('#maxDate').val();
                        data.model = $('#model').val();
                        data.event = $('#event').val();
                    }
                },
                "initComplete": function() {
                    let api = this.api();
                    let requestTimer = false;
                    let searchDelay = 1000;
                    
                    $('#audit-table_filter input[type="search"]')
                        .unbind() // Unbind previous default bindings
                        .bind("input propertychange", function(e) { // Bind the timeout search
                            let elem = $(this);

                            if (requestTimer)
                            {
                                window.clearTimeout(requestTimer);

                                requestTimer = false;
                            }

                            requestTimer = setTimeout(function () {
                                api.search($(elem).val()).draw();
                            }, searchDelay);
                        });
                },
                "drawCallback": function() {
                    convertToLocalTimestamp();

                    toggleDataTablePagination(this, '#audit-table_paginate');

                    scrollToTop();
                },
                "order": [
                    [0, 'desc']
                ],
                "columns": [
                    {
                        "data": "id"
                    },
                    {
                        "data": "date",
                        render: function(data) {
                            return '<span class="local-timestamp">' + data + '</span>';
                        }
                    },
                    {
                        "data": "ip_address"
                    },
                    {
                        "data": "event",
                        render: function(data) {
                            return '<span style="text-transform: capitalize;">' + data + '</span>';
                        }
                    },
                    {
                        "data": "user"
                    },
                    {
                        "data": "description"
                    },
                    {
                        "data": "auditable_name"
                    },
                    {
                        "data": "new_values",
                        render: function(data) {
                            let changes = '';
                            
                            $.each(JSON.parse(data), function(key, val) {
                                if (key != 'data')
                                {
                                    span.innerHTML = val;
                                    val = escapeHtml(span.innerText);
                                                                       
                                    let max = 30;
                                    let tooltip = (val && val.length > max) ? true : false;

                                    val = (tooltip) ?
                                        val.substring(0, max) + '<span class="info-icon-black" data-bs-toggle="tooltip" data-bs-placement="right" title="' + val + '"></span>' : val;

                                    if (key == 'status') {
                                        val = getValueStyle(val);
                                    }

                                    changes += '<b style="text-transform: capitalize;">' + key.replace(/_/g, ' ') + '</b>: ' + val + '<br \>';
                                }
                            });

                            return changes;
                        },
                        "orderable": false
                    },
                    {
                        "data": "changes",
                        render: function(data, type, row) {
                            let has_changes = (row.new_values && row.new_values != '[]') ? true : false;
                            
                            return '<div class="d-flex justify-content-center">' +
                                        '<button class="show-changes icon-show btn-unstyle' + (!has_changes ? ' icon-show-deactivated' : '') + '" data-bs-toggle="tooltip"' +
                                            'title="View changes" type="button" item-id="' + row.id + '">' +
                                        '</button>' +
                                   '</div>';
                        },
                        "orderable": false
                    }
                ]
            });
        });
        
        $(document).on('show', '#minDate', function() {
            if (datepickerLimit) {
                $(this).datepicker('setEndDate', $('#maxDate').datepicker('getDate'));
                datepickerLimit = false;
            }
        });

        $(document).on('show', '#maxDate', function() {
            if (datepickerLimit) {
                $(this).datepicker('setStartDate', $('#minDate').datepicker('getDate'));
                datepickerLimit = false;
            }
        });
        
        $(document).on('change', '.datepicker, #model, #event', function() {
            auditTable.draw();
        });

        $(document).on('click', '.show-changes', function() {
            $('.loader').fadeIn();
            let id = $(this).attr('item-id');

            $.ajax({
                'url': section + '/changes/show',
                success: function(data) {
                    let obj = {
                        'xlarge': true,
                        'action': 'show',
                        'title': 'Changes',
                        'html': data
                    }
                    setModal(obj);

                    initModalDataTable(id);

                    pageModal.show();
                }
            });
        });

        function initModalDataTable(id)
        {
            auditChangesTable = $('#audit-changes-table').DataTable({
                "ajax": section + '/changes/list/' + id,
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#audit-changes-table_paginate');
                },
                "ordering": false,
                "searching": false,
                "paging": false,
                "info": false,
                "columns": [
                    {
                        "data": "old_values",
                        render: function(data, type, row) {
                            if (row.old_values && row.old_values != '[]') {
                                let obj = JSON.parse(row.old_values);

                                return getValue(obj);
                            }

                            return '';
                        }
                    },
                    {
                        "data": "new_values",
                        render: function(data, type, row) {
                            if (row.new_values && row.new_values != '[]') {
                                let obj = JSON.parse(row.new_values);
                                
                                return getValue(obj);
                            }

                            return '';
                        }
                    }
                ]
            });
        }

        function getValue(obj)
        {
            try
            {
                if (obj.data) {
                    return '<pre class="pre-wrap">' + escapeHtml(JSON.stringify(JSON.parse(obj.data), null, 2)) + '</pre>';
                }

                return '<pre class="pre-wrap">' + escapeHtml(JSON.stringify(obj, null, 2)) + '</pre>';
            }
            catch {
                return '<pre class="pre-wrap">' + escapeHtml(JSON.stringify(obj, null, 2)) + '</pre>';
            }
        }

        function getValueStyle(val)
        {
            switch (val)
            {
                case 'Blocked':
                case 'Unapproved':
                    return `<span class="text-danger">${val}</span>`;
                case 'Approved':
                    return `<span style="color: #3C58CF;">${val}</span>`;
                case 'Published':
                    return `<span style="color: #004087;">${val}</span>`;
                case 'Calculated':
                case 'Submitted':
                case 'Finalised':
                    return `<span class="text-success">${val}</span>`;
                default:
                    return val;
            }
        }

        function escapeHtml(text)
        {
            let map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
  
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

    </script>
@endsection
