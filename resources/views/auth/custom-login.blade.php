@extends('layouts.app')

@section('content')
<div class="login-page d-flex justify-content-center align-items-center min-vh-100 bg-white">
    <div class="login-card shadow-lg rounded-4 p-4 p-md-5 bg-white text-center">
        <h3 class="fw-bold text-dark mb-2">Welcome Back</h3>
        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email -->
            <div class="mb-3 text-start">
                <label for="email" class="form-label fw-semibold">Email Address</label>
                <input type="email" id="email" name="email"
                    class="form-control form-control-lg rounded-3 @error('email') is-invalid @enderror"
                    value="{{ old('email') }}" required autofocus placeholder="Enter your email">
                @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Password -->
            <div class="mb-3 text-start">
                <label for="password" class="form-label fw-semibold">Password</label>
                <input type="password" id="password" name="password"
                    class="form-control form-control-lg rounded-3 @error('password') is-invalid @enderror"
                    required placeholder="Enter your password">
                @error('password')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Login Button -->
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-dark btn-lg rounded-3">Login</button>
            </div>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted">© {{ date('Y') }} Beautiflie. All Rights Reserved.</small>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
body, html {
    background-color: #ffffff !important;
    height: 100%;
    margin: 0;
}

.login-page {
    min-height: 100vh;
}

.login-card {
    width: 100%;
    max-width: 400px;
    border: 1px solid #f1f1f1;
}

.form-control {
    border: 1px solid #ddd;
    box-shadow: none !important;
}

.form-control:focus {
    border-color: #000;
    box-shadow: 0 0 0 0.2rem rgba(0,0,0,0.05);
}

.btn-dark {
    background-color: #000;
    border: none;
}

.btn-dark:hover {
    background-color: #333;
}

.text-muted {
    font-size: 0.9rem;
}
</style>
@endsection
