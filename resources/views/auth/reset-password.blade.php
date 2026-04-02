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
                                <circle cx="100" cy="100" r="80" fill="#e8f5e9" opacity="0.5" />
                                <rect x="75" y="70" width="50" height="60" rx="5" fill="#4CAF50" />
                                <circle cx="100" cy="100" r="15" fill="#fff" />
                                <path d="M90 100 L95 105 L110 90" stroke="#4CAF50" stroke-width="3" stroke-linecap="round"
                                    stroke-linejoin="round" fill="none" />
                                <rect x="85" y="140" width="30" height="8" rx="2" fill="#4CAF50" />
                                <circle cx="100" cy="160" r="8" fill="#4CAF50" />
                            </svg>
                        </div>
                    </div>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('password.store') }}">
                                @csrf

                                <!-- Password Reset Token -->
                                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                                <!-- Email Address -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">{{ __('Email') }}</label>
                                    <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" id="email"
                                        name="email" value="{{ old('email', $request->email) }}" required autofocus
                                        autocomplete="username">
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

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Reset Password') }}
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