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
                                <li class="breadcrumb-item active"><a href="#">{{ __('Users') }}</a></li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>

            <div class="row ">
                <div class="col-10 offset-1 ps-0">
                    <h1 dusk="users_title" class="indicators-title">{{ __('Manage Users') }}</h1>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])

            @php
                $is_admin = (Auth::user()->isAdmin());
            @endphp

            <div class="row mt-5">
                <div class="col-10 offset-1 ps-0 pe-0">
                    <div class="table-section col-12 mt-2">
                        <h2 class="mb-2">Users</h2>
                        <table dusk="users_table" id="users-table" class="display enisa-table-group">
                            <thead>
                                <tr>
                                    <th><input class="form-check-input" type="checkbox" id="item-select-all"></th>
                                    <th></th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Role') }}</th>
                                    <th>{{ __('Country') }}</th>
                                    <th>{{ __('Last Login') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-10 offset-1 d-flex gap-2 justify-content-end ps-0 pe-0">
                    @if ($is_admin)
                        <button dusk="delete_selected_users" type="button" class="btn btn-enisa multiple-delete" disabled>{{ __('Delete Selected Users') }}</button>
                    @endif
                    <button dusk="edit_selected_users" type="button" class="btn btn-enisa multiple-edit" disabled>{{ __('Edit Selected Users') }}</button>
                </div>
            </div>

        </div>
        <div dusk="user_manage_modal" class="modal fade" id="pageModal" tabindex="-1" aria-labelledby="pageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header p-3">
                        <h5 dusk="user_manage_modal_title" class="modal-title" id="pageModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-3"></div>
                    <div class="modal-footer justify-content-between">
                        <button dusk="user_manage_modal_close" type="button" class="btn btn-enisa" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button dusk="user_manage_modal_process" type="button" class="btn btn-enisa process-data" id='process-data'></button>
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
        let section = '/user';
        let user_id = "{{ Auth::user()->id }}";
        let is_admin = "{{ $is_admin }}";

        $(document).ready(function() {
            table = $('#users-table').DataTable({
                "ajax": section + '/list',
                "drawCallback": function() {
                    toggleDataTablePagination(this, '#users-table_paginate');
                    
                    $('#item-select-all')
                        .trigger('update_select_all')
                        .trigger('update_buttons');
                },
                "order": [
                    [1, 'asc'], // Sort by role order
                    [6, 'asc']  // Sort by country
                ],
                "columns": [
                    {
                        "data": "id",
                        render: function(data, type, row) {
                            let can_select = (row.id == user_id) ? false : true;
                            
                            return "<input dusk='datatable_user_select_" + row.id + "' class='item-select form-check-input' type='checkbox' id='item-" + data + "'" + (can_select ? '' : ' disabled') + "\>";
                        },
                        "orderable": false
                    },
                    {
                        "data": "role_order",
                        "visible": false,
                        "searchable": false
                    },
                    {
                        "data": "name",
                        render: function(data, type, row) {
                            return '<div dusk="datatable_user_name_' + row.id + '" class="name" data-id="' + row.id + '">' + row.name + '</div>';
                        }
                    },
                    {
                        "data": "email",
                        render: function(data, type, row) {
                            let dusk = 'dusk="datatable_user_email_' + row.id + '"';

                            if (row.email) {
                                return '<div ' + dusk + ' class="word-break">' + row.email + '</div>';
                            }
                            else {
                                return '<div ' + dusk + ' class="d-flex justify-content-center">-</div>';
                            }
                        }
                    },
                    {
                        "data": "role_name",
                        render: function(data, type, row) {
                            let dusk = 'dusk="datatable_user_role_' + row.id + '"';

                            if (row.role_name) {
                                return '<div ' + dusk + '>' + row.role_name + '</div>';
                            }
                            else {
                                return '<div ' + dusk + ' class="d-flex justify-content-center">-</div>';
                            }
                        }
                    },
                    {
                        "data": "country",
                        render: function(data, type, row) {
                            let dusk = 'dusk="datatable_user_country_' + row.id + '"';

                            if (row.country) {
                                return '<div ' + dusk + '>' + row.country + '</div>';
                            }
                            else {
                                return '<div ' + dusk + ' class="d-flex justify-content-center">-</div>';
                            }
                        }
                    },
                    {
                        "data": "last_login_at",
                        render: function(data, type, row) {
                            if (type === 'sort') {
                                return data;
                            }

                            let dusk = 'dusk="datatable_user_last_login_' + row.id + '"';
                            
                            if (row.last_login_at)
                            {
                                let last_login = new Date(row.last_login_at).toLocaleDateString('en-GB').split('/').join('-');

                                return '<div ' + dusk + ' style="width: 81px;">' + last_login + '</div>';
                            }
                            else {
                                return '<div ' + dusk + ' class="d-flex justify-content-center">-</div>';
                            }
                        }
                    },
                    {
                        "data": "blocked",
                        render: function(data, type, row) {
                            let dusk_status_switch = 'dusk="datatable_user_status_switch_' + row.id + '"';
                            let dusk_status_text = 'dusk="datatable_user_status_text_' + row.id + '"';

                            let can_toggle = (row.id == user_id) ? false : true;

                            return data ?
                                `<div class="d-flex gap-2">
                                    <div class="form-check form-switch">
                                        <input ${dusk_status_switch} type="checkbox" class="form-check-input switch unchecked-blocked-style ${(can_toggle ? '' : 'switch-deactivated')} "name="blockSwitch" id="blockSwitch" item-id="${row.id}">
                                    </div>
                                    <span ${dusk_status_text} class="text-danger pt-1">{{ __('Blocked') }}</span>
                                </div>` :
                                `<div class="d-flex gap-2">
                                    <div class="form-check form-switch">
                                        <input ${dusk_status_switch} type="checkbox" class="form-check-input switch ${(can_toggle ? '' : 'switch-deactivated')} "name="blockSwitch" id="blockSwitch" checked item-id="${row.id}">
                                    </div>
                                    <span ${dusk_status_text} class="pt-1">{{ __('Enabled') }}</span>
                                </div>`;
                        }
                    },
                    {
                        "data": "actions",
                        render: function(data, type, row) {
                            let can_delete = (row.id == user_id) ? false : true;

                            let edit_click = " onclick='editUser(" + JSON.stringify(row) + ");'";
                            let delete_click = " onclick='deleteUser(" + JSON.stringify(row) + ");'";

                            let delete_button = '';
                            if (is_admin) {
                                delete_button =
                                    `<button dusk="datatable_user_delete_${row.id}" class="icon-bin btn-unstyle ${(can_delete ? '' : 'icon-bin-deactivated')} "data-bs-toggle="tooltip"
                                        title="Delete user" type="button" ${delete_click} ${(!can_delete ? 'disabled' : '')}>
                                    </button>`;
                            }

                            return `<div class="d-flex justify-content-center">
                                        <button dusk="datatable_user_edit_${row.id}" class="icon-edit btn-unstyle "data-bs-toggle="tooltip"
                                            title="Edit user" type="button" ${edit_click}>
                                        </button>
                                        ${delete_button}
                                   </div>`;
                        },
                        "orderable": false
                    }
                ]
            });
        });

        $(document).on('change update_buttons', '#item-select-all, .item-select', function() {
            $('.multiple-delete, .multiple-edit').prop('disabled', (!getDataTableCheckboxesCheckedAllPages()));
        });

        $(document).on('change', 'input[name="blockSwitch"]', function() {
            $('.loader').fadeIn();

            $.ajax({
                'url': section + '/block/toggle/' + $(this).attr('item-id'),
                'type': 'post',
                success: function(data) {
                    table.ajax.reload(null, false);
                },
                error: function(req) {
                    table.ajax.reload(null, false);

                    setAlert({
                        'status': 'error',
                        'msg': req.responseJSON.error
                    });
                }
            }).done(function () {
                $('#item-select-all').prop('checked', false);
            });
        });

        $(document).on('change', '.form-switch input:checkbox', function() {
            if ($(this).is(':checked')) {
                $(this).removeClass('unchecked-blocked-style');
            }
            else {
                $(this).addClass('unchecked-blocked-style');
            }
        });

        $(document).on('click', '.multiple-delete', function() {
            let data = `<input hidden id="item-route"/>
                        <div dusk="user_delete_modal_text" class="warning-message">
                            Selected users will be deleted. Are you sure?
                        </div>`;

            let obj = {
                'modal': 'warning',
                'action': 'delete',
                'route': section + '/multiple/delete',
                'title': 'Delete Users',
                'html': data,
                'btn': 'Delete'
            };
            setModal(obj);

            pageModal.show();
        });

        $(document).on('click', '.multiple-edit', function() {
            $('.loader').fadeIn();
            let data = getFormData();
            let users = data.get('datatable-selected').split(',');
            if (users.length == 1)
            {
                let row = {
                    'id': users[0],
                    'name': $('.name[data-id="' + users[0] + '"]').text()
                };

                editUser(row);

                return;
            }

            $.ajax({
                'url': section + '/multiple/edit',
                'data': {
                    'users': data.get('datatable-selected')
                },
                success: function(data) {
                    let obj = {
                        'action': 'edit',
                        'route': section + '/multiple/update',
                        'title': 'Edit Users',
                        'html': data
                    };
                    setModal(obj);

                    pageModal.show();
                }
            });
        });

        $(document).on('click', '#process-data', function() {
            $('.loader').fadeIn();
            
            skipFadeOut = true;
            
            let route = $('.modal #item-route').val();
            let data = getFormData();

            $.ajax({
                'url': route,
                'type': 'post',
                'data': data,
                'contentType': false,
                'processData': false,
                success: function(data) {
                    skipFadeOut = false;

                    pageModal.hide();
                    table.ajax.reload(null, false);
                },
                error: function(req) {
                    if (req.status == 302)
                    {
                        pageModal.hide();
                        window.location.href = req.responseJSON.redirect;
                    }
                    else if (req.status == 400)
                    {
                        skipFadeOut = false;

                        if (req.responseJSON.type == 'pageModalForm') {
                            showInputErrors(req.responseJSON.errors);
                        }
                    }
                    else if (req.status == 403 ||
                             req.status == 405)
                    {
                        skipFadeOut = false;

                        pageModal.hide();
                        table.ajax.reload(null, false);

                        type = Object.keys(req.responseJSON)[0];
                        message = req.responseJSON[ type ];

                        setAlert({
                            'status': type,
                            'msg': message
                        });
                    }
                }
            }).done(function () {
                $('#item-select-all').prop('checked', false);
            });
        });
        
        function editUser(row)
        {
            $('.loader').fadeIn();

            $.ajax({
                'url': section + '/single/edit/' + row.id,
                success: function(data) {
                    let obj = {
                        'id': row.id,
                        'action': 'edit',
                        'route': section + '/single/update/' + row.id,
                        'title': 'Edit User ' + row.name,
                        'html': data
                    };
                    setModal(obj);

                    pageModal.show();
                }
            });
        }

        function deleteUser(row)
        {
            let data = `<input hidden id="item-route"/>
                        <div dusk="user_delete_modal_text" class="warning-message">
                            User '${row.name}' will be deleted. Are you sure?
                        </div>`;

            let obj = {
                'modal': 'warning',
                'id': row.id,
                'action': 'delete',
                'route': section + '/single/delete/' + row.id,
                'title': 'Delete User',
                'html': data,
                'btn': 'Delete'
            };
            setModal(obj);

            pageModal.show();
        }
    </script>
@endsection
