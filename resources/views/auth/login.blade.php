@extends('layouts.guest')


@section('content')



<section id="login-page">
    <div class="container">
        <div class="row guest-wrapper d-flex align-items-center justify-content-center">        
                <div class="col-12">
                    <div class="row">                        
                        <div class="d-flex flex-column justify-content-center align-items-center mt-3">
                            <form method="POST" action="{{ route('login') }}" id="login-form" class="form-control">
                                @csrf
            
                                <!-- Email Address -->
                                <div class="row d-flex  justify-content-center align-items-center">
                                    <x-label for="email" :value="__('Email')" class="block font-medium text-sm text-gray-700 col-2 col-form-label text-end" />
                                    <div class="col-sm-10 col-md-7 col-lg-5">
                                        <x-input id="email" class="form-control" type="email" name="email" :value="old('email')"
                                            required autofocus />
                                    </div>
            
                                </div>
            
            
                                <!-- Password -->
                                <div class="row d-flex  justify-content-center align-items-center">
                                    <x-label for="password" :value="__('Password')" class="block font-medium text-sm text-gray-700 col-2 col-form-label text-end mt-4" />
                                    <div class="col-sm-10 col-md-7 col-lg-5">
                                        <x-input id="password" class="form-control" type="password" name="password" required
                                            autocomplete="current-password" />
                                    </div>
                                </div>
            
            
            
                                <!-- Remember Me -->
                                <div class="row d-flex  justify-content-center align-items-center mt-5">
                                    <div class="col-md-2"></div>
                                    <div class="col-sm-10 col-md-7 col-lg-5">
                                        <label for="remember_me" class="inline-flex items-center form-label">
                                            <input id="remember_me" type="checkbox" class="form-label" name="remember">
                                            <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                                        </label>
                                    </div>                        
                                </div>
            
                                <div class="row d-flex  justify-content-center align-items-center ">
                                    @if (Route::has('password.request'))
                                        <div class="col-md-2"></div>
                                        <div class="col-sm-10 col-md-7 col-lg-5 d-flex justify-content-between align-items-end">
                                            {{-- <a class="underline text-sm text-gray-600 hover:text-gray-900  text-muted"
                                            href="{{ route('password.request') }}">
                                            {{ __('Forgot your password?') }}
                                            </a>                               --}}
                                            <a class="underline text-sm text-gray-600 hover:text-gray-900  text-muted"
                                            href="#">
                                            {{ __('Forgot your password?') }}
                                            </a>  
                                        @endif
                                            <x-button class="ml-3 btn btn-primary">
                                                {{ __('Log in') }}
                                            </x-button>
                                        </div> 
                                        </div>                        
                                </div>
                    </div> 
                </div>
            </div>        
        
        
    </div>
     
    @endsection   
    

</section>
