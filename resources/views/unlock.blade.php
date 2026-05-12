<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Access Locked</title>
</head>
<body>

<form id="access-lock-form" action="{{ route('access-lock.unlock') }}" method="POST" style="display:none">
    @csrf
    <input type="hidden" name="intended"  value="{{ $intended ?? '/' }}">
    <input type="hidden" name="password"  id="access-lock-password">
</form>

<script>
(function () {
    @if(!empty($error))
    var message = {{ Illuminate\Support\Js::from('⚠️ ' . $error . "\n\nEnter access password:") }};
    @else
    var message = 'Enter access password:';
    @endif

    var password = window.prompt(message);

    if (password === null) {
        // User dismissed — show a minimal fallback so the page isn't just blank.
        document.write('<p style="font-family:sans-serif;padding:2rem">Access is locked. Reload the page to try again.</p>');
        return;
    }

    document.getElementById('access-lock-password').value = password;
    document.getElementById('access-lock-form').submit();
}());
</script>

</body>
</html>
