@extends('layouts.app')

@section('title', 'Index')

@section('content')

    <main id="enisa-main" class="bg-white">
        <div class="container-fluid">

            <div class="row ps-0">
                <div class="col ps-0 offset-md-1">
                    <div class="enisa-breadcrump">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb text-end">
                                <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                                <li class="breadcrumb-item active"><a href="#">{{ __('Indexes') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-10 offset-1 ps-0">
                    <h1 dusk="indexes_title">{{ __('Manage Indexes') }}</h1>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            <div class="row mt-5">
                <div class="col-10 offset-1 table-section">
                    <h2 class="mb-2">Indexes <button dusk="create_index" class="clickable-icon btn-unstyle item-create" data-bs-toggle="tooltip" title="Create index" type="button"></button></h2>
                    <table dusk="indexes_table" id="index-management-table" class="display enisa-table-group">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Year') }}</th>
                                <th>{{ __('Created By') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

        </div>
        <div dusk="index_manage_modal" class="modal fade" id="pageModal" tabindex="-1" aria-labelledby="pageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header p-3">
                        <h5 dusk="index_manage_modal_title" class="modal-title" id="pageModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3"></div>
                    <div class="modal-footer justify-content-between">
                        <button dusk="index_manage_modal_close" type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button dusk="index_manage_modal_process" type="button" class="btn btn-enisa process-data" id='process-data'></button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>
    <script src="{{ mix('mix/js/modal.js') }}" defer></script>

    <script>
        var pageModal = new bootstrap.Modal(document.getElementById('pageModal'));
        let table;
        let section = '/index';
        
        $(document).ready(function() {
            table = $('#index-management-table').DataTable({
                "ajax": section + '/list',
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#index-management-table_paginate');
                },
                "order": [
                    [1, 'asc'], // Sort by year
                    [0, 'asc']  // Sort by name
                ],
                "columns": [
                    {
                        "data": "name",
                        render: function(data, type, row) {
                            return '<div dusk="datatable_index_name_' + row.id + '">' + row.name + '</div>';
                        }
                    },
                    {
                        "data": "year",
                        render: function(data, type, row) {
                            return '<div dusk="datatable_index_year_' + row.id + '">' + row.year + '</div>';
                        }
                    },
                    {
                        "data": "user",
                        render: function(data, type, row) {
                            return '<div dusk="datatable_index_created_by_' + row.id + '">' + row.user + '</div>';
                        }
                    },
                    {
                        "data": "status",
                        render: function(data, type, row) {
                            let obj = getStatusData(row.draft);

                            return `<div class="d-flex justify-content-center">
                                        <button dusk="datatable_index_status_${row.id}" class="btn-${obj.status_style} btn-label pointer-events-none">${obj.status_label}</button>
                                    </div>`;
                        }
                    },
                    {
                        "data": "name",
                        render: function(data, type, row) {
                            return `<div class="d-flex justify-content-center">
                                        <button dusk="datatable_edit_index_${row.id}" class="icon-edit btn-unstyle" data-bs-toggle="tooltip"
                                            title="Edit index" onclick="location.href=\'/index/show/${row.id}\';"></button>
                                        <button dusk="datatable_delete_index_${row.id}" class="icon-bin btn-unstyle item-delete ${(!row.draft ? 'icon-bin-deactivated' : '')}" data-bs-toggle="tooltip"
                                            title="Delete index" item-id="${row.id}" item-name="${row.name}" ${(!row.draft ? 'disabled' : '')}></button>
                                   </div`;
                        },
                        "orderable": false
                    }
                ]
            });
        });

        $(document).on('click', '.item-create', function() {
            $('.loader').fadeIn();
            
            $.ajax({
                'url': section + '/create/',
                success: function(data) {
                    let obj = {
                        'action': 'create',
                        'route': section + '/store',
                        'title': 'New Index',
                        'html': data
                    };
                    setModal(obj);

                    pageModal.show();
                }
            });
        });

        $(document).on('click', '.item-delete', function() {
            let id = $(this).attr('item-id');
            let name = $(this).attr('item-name');
            let data = `<input hidden id="item-id"/>
                        <input hidden id="item-action"/>
                        <input hidden id="item-route"/>
                        <div dusk="index_delete_modal_text" class="warning-message">
                            Index '${name}' will be deleted. Are you sure?
                        </div>`;

            let obj = {
                'modal': 'warning',
                'id': id,
                'action': 'delete',
                'route': section + '/delete/' + id,
                'title': 'Delete Index',
                'html': data,
                'btn': 'Delete'
            };
            setModal(obj);

            pageModal.show();
        });

        $(document).on('click', '#process-data', function() {
            $('.loader').fadeIn();
            let route = $('.modal #item-route').val();
            let data = getFormData();
            if ($('.modal #item-id').length &&
                $('.modal #item-id').val())
            {
                data.append('id', $('.modal #item-id').val());
            }
            
            $.ajax({
                'url': route,
                'type': 'post',
                'data': data,
                'contentType': false,
                'processData': false,
                success: function() {
                    pageModal.hide();
                    table.ajax.reload(null, false);
                },
                error: function(req) {
                    if (req.status == 400) {
                        showInputErrors(req.responseJSON);
                    }
                    else if (req.status == 403 ||
                             req.status == 405)
                    {
                        pageModal.hide();

                        type = Object.keys(req.responseJSON)[0];
                        message = req.responseJSON[ type ];

                        setAlert({
                            'status': type,
                            'msg': message
                        });
                    }
                }
            });
        });

        function getStatusData(is_draft)
        {
            let obj = {};

            switch (is_draft)
            {
                case 0:
                    obj.status_label = 'Published';
                    obj.status_style = 'positive';
                    break;
                case 1:
                    obj.status_label = 'Unpublished';
                    obj.status_style = 'positive-invert';
                    break;
            }
            
            return obj;
        }
    </script>
@endsection
