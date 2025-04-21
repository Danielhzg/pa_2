<form method="POST" action="{{ route('verifyOtp') }}">
    @csrf
    <label for="otp">Enter OTP:</label>
    <input 
        type="text" 
        id="otp" 
        name="otp" 
        autocomplete="new-password" <!-- Prevent auto-fill -->
        placeholder="Enter OTP" 
        required 
    />
    <button type="submit">Verify</button>
</form>
