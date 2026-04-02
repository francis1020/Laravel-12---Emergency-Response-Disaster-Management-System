@extends('layouts.app')

@section('content')
    <div class="min-vh-100 d-flex flex-column py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="text-center">
                        <div class="illustration-container">
                            <svg width="250" height="250" viewBox="0 0 200 200" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <circle cx="100" cy="100" r="80" fill="#e1f5fe" opacity="0.5" />
                                <rect x="60" y="70" width="80" height="60" rx="5" fill="#03A9F4" />
                                <path d="M60 70 L100 100 L140 70" stroke="#0277BD" stroke-width="3" fill="none" />
                                <circle cx="100" cy="100" r="12" fill="#fff" />
                                <path d="M92 100 L96 104 L108 92" stroke="#03A9F4" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" fill="none" />
                            </svg>
                        </div>
                    </div>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <div class="mb-4 text-muted">
                                {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
                            </div>

                            @if (session('status') == 'verification-link-sent')
                                <div class="alert alert-success mb-4">
                                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                                </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-center">
                                <form method="POST" action="{{ route('verification.send') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Resend Verification Email') }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-link text-decoration-none">
                                        {{ __('Log Out') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection