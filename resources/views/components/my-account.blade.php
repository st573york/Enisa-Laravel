@extends('layouts.app')

@section('title', 'Index')

@section('content')

    <main id="enisa-main" class="bg-white">
        <div class="container-fluid">

            @if (!Auth::user()->blocked)
                <div class="row ">
                    <div class="col-10 offset-1 ps-0">
                        <div class="enisa-breadcrump p-1 d-flex">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb text-end">
                                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                                    <li class="breadcrumb-item active"><a href="#">My Account</a></li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row">
                <div class="col-lg-5 offset-1 ps-0">
                    <h1>My Account</h1>
                </div>
            </div>

            @include('components.alert', ['type' => 'pageAlert'])
            @include('ajax.user-details')

        </div>

    </main>

    <script src="{{ mix('mix/js/main.js') }}" defer></script>
    <script src="{{ mix('mix/js/alert.js') }}" defer></script>

    <script>
        let user = <?php echo json_encode(Auth::user()); ?>;
        let permissions = <?php echo json_encode(Auth::user()->permissions); ?>;

        $(document).ready(function() {
            if (user.blocked == 1)
            {
                if (permissions.length) {
                    setAlert({
                        'status': 'error',
                        'msg': 'Access blocked. An administrator will need to unblock your account.'
                    });
                }
                else {
                    setAlert({
                        'status': 'error',
                        'msg': 'Thanks for registering. The administrators will check and approve your account asap and you will be notified.'
                    });
                }
            }
        });

        $(document).on('click', '#process-data', function() {
            $('.loader').fadeIn();
            
            let data = getFormData();
            if ($('#country_code').is(':disabled')) {
                data.append('country_code', $('#country_code').val());
            }
            
            $.ajax({
                'url': '/my-account/update',
                'type': 'post',
                'data': data,
                'contentType': false,
                'processData': false,
                success: function(response) {
                    $('#country_code').prop('disabled', true);

                    setAlert({
                        'status': 'success',
                        'msg': response.success
                    });
                },
                error: function(req) {
                    showInputErrors(req.responseJSON);
                }
            });
        });
    </script>
@endsection
