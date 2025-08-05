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
                                <li class="breadcrumb-item active"><a href="#">{{ __('Invitations') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-5 offset-1 ps-0">
                    <h1 dusk="invitations_title">{{ __('Manage Invitations') }}</h1>
                </div>
                <div class="col-lg-5 col-11 pe-0">
                    <div class="d-flex gap-2 justify-content-end">
                        <button dusk="invite_new_user" type="button" class="btn btn-enisa invite-user">{{ __('Invite new user') }}</button>
                    </div>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            <div class="row mt-5">
                <div class="col-10 offset-1 ps-0 pe-0">
                    <div class="table-section col-12 mt-2">
                        <h2 class="mb-2">Invitations</h2>
                        <table dusk="invitations_table" id="invitations-table" class="display enisa-table-group">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Email Address') }}</th>
                                    <th>{{ __('Country') }}</th>
                                    <th>{{ __('Role') }}</th>
                                    <th>{{ __('Invited By') }}</th>
                                    <th>{{ __('Invitation Date') }}</th>
                                    <th>{{ __('Registration Date') }}</th>
                                    <th>{{ __('Status') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

        </div>
        <div dusk="invitation_manage_modal" class="modal fade" id="pageModal" tabindex="-1" aria-labelledby="pageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header p-3">
                        <h5 dusk="invitation_manage_modal_title" class="modal-title" id="pageModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3"></div>
                    <div class="modal-footer justify-content-between">
                        <button dusk="invitation_manage_modal_close" type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button dusk="invitation_manage_modal_process" type="button" class="btn btn-enisa process-data" id='process-data'></button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/modal.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>

    <script>
        var pageModal = new bootstrap.Modal(document.getElementById('pageModal'));
        let table;
        let section = '/invitation';

        $(document).ready(function() {
            table = $('#invitations-table').DataTable({
                "ajax": section + '/list',
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#invitations-table_paginate');
                },
                "order": [
                    [5, 'desc']
                ],
                "columns": [
                    {
                        "data": "name",
                        render: function(data, type, row) {
                            return '<div dusk="datatable_user_name_' + row.id + '">' + row.name + '</div>';
                        }
                    },
                    {
                        "data": "email",
                        render: function(data, type, row) {
                            return '<div dusk="datatable_user_email_' + row.id + '" class="word-break">' + row.email + '</div>';
                        }
                    },
                    {
                        "data": "country",
                        render: function(data, type, row) {
                            return '<div dusk="datatable_user_country_' + row.id + '">' + row.country + '</div>';
                        }
                    },
                    {
                        "data": "role",
                        render: function(data, type, row) {
                            return '<div dusk="datatable_user_role_' + row.id + '">' + row.role + '</div>';
                        }
                    },
                    {
                        "data": "invited_by",
                        render: function(data, type, row) {
                            return '<div dusk="datatable_user_invited_by_' + row.id + '">' + row.invited_by + '</div>';
                        }
                    },
                    {
                        "data": "invited_at",
                        render: function(data, type, row) {
                            if (type === 'sort') {
                                return data;
                            }

                            let dusk = 'dusk="datatable_user_invited_at_' + row.id + '"';
                            
                            if (row.invited_at)
                            {
                                let invitation_date = new Date(row.invited_at).toLocaleDateString('en-GB').split('/').join('-');

                                return '<div ' + dusk + ' style="width: 81px;">' + invitation_date + '</div>';
                            }
                            else {
                                return '<div ' + dusk + ' class="d-flex justify-content-center">-</div>';
                            }
                        }
                    },
                    {
                        "data": "registered_at",
                        render: function(data, type, row) {
                            if (type === 'sort') {
                                return data;
                            }

                            let dusk = 'dusk="datatable_user_registered_at_' + row.id + '"';
                            
                            if (row.registered_at)
                            {
                                let registration_date = new Date(row.registered_at).toLocaleDateString('en-GB').split('/').join('-');

                                return '<div ' + dusk + ' style="width: 81px;">' + registration_date + '</div>';
                            }
                            else {
                                return '<div ' + dusk + ' class="d-flex justify-content-center">-</div>';
                            }
                        }
                    },
                    {
                        "data": "status",
                        render: function(data, type, row) {
                            let obj = getRowData(row);

                            return `<div class="d-flex justify-content-center">
                                        <button dusk="datatable_user_status_text_${row.id}" class="btn-${obj.status_style} btn-label pointer-events-none" type="button">${obj.status_label}</button>
                                    </div>`;
                        }
                    }
                ]
            });
        });

        $(document).on('click', '.invite-user', function() {
            $('.loader').fadeIn();
    
            $.ajax({
                'url': section + '/create',
                success: function(data) {
                    let obj = {
                        'action': 'create',
                        'route': section + '/store',
                        'title': 'Invite new user',
                        'html': data,
                        'btn': 'Invite'
                    };
                    setModal(obj);

                    pageModal.show();
                }
            });
        });


        $(document).on('click', '#process-data', function() {
            $('.loader').fadeIn();
                        
            let route = $('.modal #item-route').val();
            let data = getFormData();

            $.ajax({
                'url': route,
                'type': 'post',
                'data': data,
                'contentType': false,
                'processData': false,
                success: function(data) {
                    pageModal.hide();
                    table.ajax.reload(null, false);
                },
                error: function(req) {
                    if (req.status == 400)
                    {
                        if (req.responseJSON.type == 'pageModalForm') {
                            showInputErrors(req.responseJSON.errors);
                        }
                    }
                    
                    if (req.responseJSON.type &&
                        req.responseJSON.error)
                    {
                        if (req.responseJSON.type == 'pageAlert') {
                            pageModal.hide();
                        }

                        setAlert({
                            'type': req.responseJSON.type,
                            'status': 'error',
                            'msg': req.responseJSON.error
                        });
                    }
                }
            });
        });

        function getRowData(row)
        {
            let obj = {};
            
            switch (row.status_id)
            {
                case 1:
                    obj.status_label = 'Pending';
                    obj.status_style = 'positive-invert';

                    break;
                case 2:
                    obj.status_label = 'Registered';
                    obj.status_style = 'positive';

                    break;
                case 3:
                    obj.status_label = 'Expired';
                    obj.status_style = 'negative';

                    break;
            }
            
            return obj;
        }
    </script>
@endsection
