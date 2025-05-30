<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Welcome to Planet Nine!</title>
    <style>
        body {
            background-color: #f4f4f7;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #4f46e5;
        }

        .header {
            background-color: #4f46e5;
            color: white;
            padding: 30px;
            text-align: center;
        }

        .content {
            padding: 30px;
            text-align: center;
        }

        .content h1 {
            margin-bottom: 10px;
        }

        .content p {
            margin: 10px 0 20px 0;
            font-size: 16px;
            line-height: 1.5;
        }

        .button {
            display: inline-block;
            padding: 12px 25px;
            margin-top: 20px;
            background-color: #4f46e5;
            color: white;
            text-decoration: none;
            font-size: 16px;
            border-radius: 5px;
            font-weight: bold;
        }

        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h2>Welcome to Planet Nine 🚀</h2>
        </div>

        <div class="content">
            <h1>Hello {{ $user->name }}!</h1>
            <p>Thank you for joining Planet Nine. We're excited to have you on board.</p>
            <p>To get started, please complete your registration by clicking the button below:</p>

            <a href="{{ route('welcome-to-planetnine-register', ['user' => $user->id]) }}"
                style="display: inline-block; padding: 12px 25px; margin-top: 20px; background-color: #4f46e5; color: #ffffff; text-decoration: none; font-size: 16px; border-radius: 5px; font-weight: bold;">
                Complete Registration
            </a>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} Planet Nine. All rights reserved.
        </div>
    </div>

</body>

</html>