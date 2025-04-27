<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome!</title>
</head>
<body>
    <h1>Welcome to Planet Nine, {{ $user->name }}!</h1>
    <p>Thank you for joining us. Please complete your registration.</p>
    <p><a href="{{ url('/welcome-to-planetnine/register') }}">Complete Registration</a></p>
</body>
</html>