@extends('layouts.guest')

@section('content')

<section id="login-page">
    <div class="container">
        <div class="row guest-wrapper d-flex align-items-center justify-content-center">
            <div class="col-12">
                <div class="row">
                    <div class="d-flex align-items-center justify-content-center">
                        <div>
                            <span class="error-icon"></span>
                            <span class="h5 m-0" style="color: var(--red)">You have been logged out {{ (isset($reason)) ? $reason : '' }}</span>
                            <a href="/" class="ml-3 btn btn-primary"> {{ __('Log in') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection
