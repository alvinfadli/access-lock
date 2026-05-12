<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Access Locked</title>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .lock-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            line-height: 1;
        }

        .card h1 {
            font-size: 1.375rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #0f172a;
        }

        .card p.subtitle {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 1.75rem;
            line-height: 1.5;
        }

        .error-box {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .form-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.375rem;
        }

        input[type="password"] {
            width: 100%;
            padding: 0.625rem 0.875rem;
            font-size: 0.9375rem;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            background: #f9fafb;
            color: #111827;
        }

        input[type="password"]:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            background: #ffffff;
        }

        button[type="submit"] {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: #ffffff;
            background-color: #6366f1;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.15s, transform 0.1s;
            margin-top: 0.25rem;
        }

        button[type="submit"]:hover {
            background-color: #4f46e5;
        }

        button[type="submit"]:active {
            transform: scale(0.98);
        }

        .prompt-notice {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 1.5rem;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="lock-icon">🔒</div>
    <h1>Access Locked</h1>
    <p class="subtitle">This page is password protected.<br>Enter the access password to continue.</p>

    @if(session('access_lock_error'))
        <div class="error-box">
            {{ session('access_lock_error') }}
        </div>
    @endif

    <form id="unlock-form" action="{{ route('access-lock.unlock') }}" method="POST">
        @csrf
        <input type="hidden" name="password" id="password-input">

        <div class="form-group">
            <label for="password-visible">Password</label>
            <input
                type="password"
                id="password-visible"
                placeholder="Enter access password"
                autocomplete="current-password"
                autofocus
            >
        </div>

        <button type="submit">Unlock Access</button>
    </form>

    <p class="prompt-notice">
        A password prompt will appear automatically if JavaScript is enabled.
    </p>
</div>

<script>
    (function () {
        // Mirror the visible input into the hidden form field on submit.
        var form = document.getElementById('unlock-form');
        var visibleInput = document.getElementById('password-visible');
        var hiddenInput = document.getElementById('password-input');

        form.addEventListener('submit', function (e) {
            hiddenInput.value = visibleInput.value;
        });

        // Show a JS prompt for quick entry; pre-fill the visible input if the user
        // entered a value so they can confirm it before the form submits.
        @if(!session('access_lock_error'))
        window.addEventListener('load', function () {
            var prompted = window.prompt('🔒 Enter access password:');
            if (prompted === null) {
                // User dismissed the prompt — let them use the manual form.
                return;
            }
            if (prompted.length > 0) {
                hiddenInput.value = prompted;
                form.submit();
            }
        });
        @endif
    })();
</script>

</body>
</html>
