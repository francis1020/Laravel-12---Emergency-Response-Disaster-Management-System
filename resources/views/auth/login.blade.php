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
                                <circle cx="100" cy="100" r="80" fill="#e3f2fd" opacity="0.5" />
                                <rect x="60" y="80" width="80" height="60" rx="5" fill="#2196F3" />
                                <circle cx="100" cy="110" r="15" fill="#fff" />
                                <path d="M85 130 L100 145 L115 130" stroke="#fff" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <rect x="50" y="50" width="20" height="20" rx="3" fill="#4CAF50" />
                                <circle cx="60" cy="60" r="3" fill="#fff" />
                            </svg>
                        </div>
                    </div>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <!-- Session Status -->
                            @if (session('status'))
                                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                                    {{ session('status') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <!-- Email Address -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" id="email"
                                        name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Password -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">{{ __('Password') }}</label>
                                    <input type="password" class="form-control form-control-sm @error('password') is-invalid @enderror"
                                        id="password" name="password" required autocomplete="current-password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Remember Me -->
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="remember_me" name="remember">
                                    <label class="form-check-label" for="remember_me">
                                        {{ __('Remember me') }}
                                    </label>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="text-decoration-none">
                                            {{ __('Forgot your password?') }}
                                        </a>
                                    @else
                                        <div></div>
                                    @endif
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Log in') }}
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