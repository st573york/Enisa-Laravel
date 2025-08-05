@extends('layouts.guest')

@section('content')

<div class="container">
    <div class="row">
        <div class="col-10 offset-1">
            <div class="d-flex flex-column align-items-center justify-content-center guest-wrapper">
                <div>
                    <div class="d-flex gap-2 align-items-center justify-content-center">
                        <span class="error-icon"></span>
                        <span dusk="error_title" class="h5 m-0" style="color: var(--red)">Invitation expired</span>
                    </div>
                    <p class="w-100 text-center mt-2">
                        @php
                            $response = '<span dusk="error_message" class="h6 m-0" style="color: var(--red); padding-top: 4px;">Your invitation link has expired. Please contact your country\'s Primary Point of Contact to request a new invitation link.</span>';
                        @endphp
                        {!! $response !!}
                    </p>
                    <hr>
                    <div class="row flex-column align-items-center">
                        <div class="col-12  msg-col mt-auto">
                            <p class="m-0"><small>Need help? Contact our support team at: <a href="#">security-index@enisa.europa.eu</a></small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
 
@endsection
