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
                                <circle cx="100" cy="100" r="80" fill="#fff3e0" opacity="0.5" />
                                <rect x="70" y="60" width="60" height="40" rx="3" fill="#FF9800" />
                                <path d="M70 60 L100 45 L130 60" stroke="#FF9800" stroke-width="3" fill="none" />
                                <line x1="100" y1="100" x2="100" y2="80" stroke="#FF9800" stroke-width="3"
                                    stroke-linecap="round" />
                                <circle cx="100" cy="120" r="20" fill="#FF9800" />
                                <path d="M85 135 L100 150 L115 135" stroke="#fff" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <div class="mb-4 text-muted">
                                {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
                            </div>

                            <!-- Session Status -->
                            @if (session('status'))
                                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                                    {{ session('status') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('password.email') }}">
                                @csrf

                                <!-- Email Address -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" id="email"
                                        name="email" value="{{ old('email') }}" required autofocus>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Email Password Reset Link') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection