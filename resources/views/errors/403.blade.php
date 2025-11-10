<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Forbidden - Planet Nine</title>
    <link rel="shortcut icon" href="https://www.planetnine.com/logo/new_favicon.png">
    @vite('resources/css/app.css')
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fc466b 0%, #3f5efb 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-container {
            background: white;
            border-radius: 20px;
            padding: 3rem 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
            margin: 1rem;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .error-code {
            font-size: 8rem;
            font-weight: 900;
            color: #fc466b;
            margin: 0;
            line-height: 1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .error-title {
            font-size: 2rem;
            font-weight: 600;
            color: #2d3748;
            margin: 1rem 0 0.5rem;
        }

        .error-message {
            font-size: 1.1rem;
            color: #718096;
            margin: 0 0 2rem;
            line-height: 1.6;
        }

        .permission-message {
            background: #fed7d7;
            border: 1px solid #feb2b2;
            color: #c53030;
            padding: 1rem;
            border-radius: 10px;
            margin: 1.5rem 0;
            font-weight: 500;
        }

        .actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #fc466b, #3f5efb);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(252, 70, 107, 0.3);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }

        .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .planet-nine-logo {
            max-width: 120px;
            margin-bottom: 1rem;
        }

        @media (max-width: 640px) {
            .error-code {
                font-size: 6rem;
            }

            .error-title {
                font-size: 1.5rem;
            }

            .error-container {
                padding: 2rem 1.5rem;
            }

            .actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="icon">🚫</div>

        <h1 class="error-code">403</h1>

        <h2 class="error-title">Access Forbidden</h2>

        <p class="error-message">
            You don't have the necessary permissions to access this resource.
        </p>

        @if(isset($exception) && $exception->getMessage())
        <div class="permission-message">
            {{ $exception->getMessage() }}
        </div>
        @endif

        <div class="actions">
            <button onclick="history.back()" class="btn btn-secondary">
                ← Go Back
            </button>

            @auth
            @if(in_array('/', auth()->user()->permissions ?? []) || in_array('/dashboard', auth()->user()->permissions ?? []) || in_array('*', auth()->user()->permissions ?? []))
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                🏠 Dashboard
            </a>
            @endif
            @else
            <a href="{{ route('login') }}" class="btn btn-primary">
                🔑 Login
            </a>
            @endauth
        </div>

        <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e2e8f0; color: #a0aec0; font-size: 0.875rem;">
            <p>&copy; {{ date('Y') }} Planet Nine. All rights reserved.</p>
        </div>
    </div>
</body>

</html>