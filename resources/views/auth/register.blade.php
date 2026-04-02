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
                                <circle cx="100" cy="100" r="80" fill="#f3e5f5" opacity="0.5" />
                                <circle cx="100" cy="80" r="30" fill="#9C27B0" />
                                <path d="M60 140 Q60 120 100 120 Q140 120 140 140" stroke="#9C27B0" stroke-width="8"
                                    fill="none" stroke-linecap="round" />
                                <rect x="75" y="150" width="50" height="20" rx="3" fill="#9C27B0" />
                                <path d="M85 160 L100 175 L115 160" stroke="#fff" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('register') }}">
                                @csrf

                                <!-- Name -->
                                <div class="mb-3">
                                    <label for="name" class="form-label">{{ __('Name') }}</label>
                                    <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror" id="name"
                                        name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Email Address -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" id="email"
                                        name="email" value="{{ old('email') }}" required autocomplete="username">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Password -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">{{ __('Password') }}</label>
                                    <input type="password" class="form-control form-control-sm @error('password') is-invalid @enderror"
                                        id="password" name="password" required autocomplete="new-password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Confirm Password -->
                                <div class="mb-3">
                                    <label for="password_confirmation"
                                        class="form-label">{{ __('Confirm Password') }}</label>
                                    <input type="password"
                                        class="form-control form-control-sm @error('password_confirmation') is-invalid @enderror"
                                        id="password_confirmation" name="password_confirmation" required
                                        autocomplete="new-password">
                                    @error('password_confirmation')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('login') }}" class="text-decoration-none">
                                        {{ __('Already registered?') }}
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Register') }}
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