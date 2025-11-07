<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code - FinTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .otp-input {
            width: 60px;
            height: 60px;
            font-size: 32px;
            text-align: center;
            margin: 0 8px;
        }
        .otp-group {
            display: flex;
            justify-content: center;
            gap: 12px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header text-center">
                        <h3 class="mb-1">Enter Verification Code</h3>
                        <p class="text-muted mb-0">We sent a 4-digit code to <strong>{{ $email }}</strong></p>
                    </div>
                    <div class="card-body">
                        @if (session('status'))
                            <div class="alert alert-info">{{ session('status') }}</div>
                        @endif

                        <form method="POST" action="{{ route('auth.otp.verify') }}" id="otp-form" class="text-center">
                            @csrf

                            <div class="otp-group mb-4">
                                <input type="text" inputmode="numeric" maxlength="1" minlength="1" name="otp_1" class="form-control otp-input @error('otp') is-invalid @enderror" autofocus>
                                <input type="text" inputmode="numeric" maxlength="1" minlength="1" name="otp_2" class="form-control otp-input">
                                <input type="text" inputmode="numeric" maxlength="1" minlength="1" name="otp_3" class="form-control otp-input">
                                <input type="text" inputmode="numeric" maxlength="1" minlength="1" name="otp_4" class="form-control otp-input">
                            </div>

                            @error('otp')
                                <div class="alert alert-danger">{{ $message }}</div>
                            @enderror

                            <button type="submit" class="btn btn-primary w-100">Verify Code</button>
                        </form>

                        <form method="POST" action="{{ route('auth.otp.resend') }}" class="mt-3 text-center">
                            @csrf
                            <button type="submit" class="btn btn-link">Resend code</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const inputs = document.querySelectorAll('.otp-input');
        inputs.forEach((input, index) => {
            input.addEventListener('input', () => {
                const value = input.value.replace(/\D/g, '');
                input.value = value;
                if (value && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Backspace' && !input.value && index > 0) {
                    inputs[index - 1].focus();
                }
            });
        });
    </script>
</body>
</html>
