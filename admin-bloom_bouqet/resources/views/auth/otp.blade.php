@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ __('Verify OTP') }}</span>
                    <a href="{{ route('register') }}" class="btn btn-link">{{ __('Back to Register') }}</a>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('otp.verify') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="otp" class="form-label">{{ __('Enter OTP') }}</label>
                            <input id="otp" type="text" class="form-control @error('otp') is-invalid @enderror" name="otp" required autofocus>
                            
                            @error('otp')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('Verify') }}</button>
                    </form>

                    <div class="mt-3 text-center">
                        <p>{{ __('Didn\'t receive the OTP?') }}</p>
                        <form method="POST" action="{{ route('otp.resend') }}">
                            @csrf
                            <button type="submit" class="btn btn-link">{{ __('Resend OTP') }}</button>
                        </form>
                    </div>

                    <div class="mt-3 text-center">
                        <p id="countdown">{{ __('Time remaining: 03:00') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let timeLeft = 180; // 3 minutes in seconds
    const countdownElement = document.getElementById('countdown');

    const timer = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(timer);
            countdownElement.textContent = '{{ __("Time expired. Please request a new OTP.") }}';
        } else {
            const minutes = Math.floor(timeLeft / 60).toString().padStart(2, '0');
            const seconds = (timeLeft % 60).toString().padStart(2, '0');
            countdownElement.textContent = `{{ __("Time remaining:") }} ${minutes}:${seconds}`;
            timeLeft--;
        }
    }, 1000);
</script>
@endsection
