<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Page Lost in Space</title>
    <link rel="shortcut icon" href="https://www.planetnine.com/logo/new_favicon.png">
    @vite('resources/css/app.css')
    <style>
        :root {
            --bg1: #0f172a;
            --bg2: #0b1220;
        }

        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: radial-gradient(1200px 600px at 10% 10%, rgba(102, 126, 234, 0.12), transparent), linear-gradient(135deg, #0f172a 0%, #0b1220 100%);
            margin: 0;
            padding: 40px 20px;
            color: #e6eef8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.02));
            border-radius: 16px;
            padding: 28px;
            max-width: 820px;
            width: 100%;
            box-shadow: 0 10px 40px rgba(2, 6, 23, 0.6);
            color: #cbd5e1;
        }

        .hero {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .code {
            font-size: 72px;
            line-height: 1;
            font-weight: 800;
            color: #a3bffa;
            text-shadow: 0 6px 30px rgba(99, 102, 241, 0.08);
        }

        .copy {
            font-weight: 700;
            color: #e6eef8;
            font-size: 20px;
        }

        .title {
            font-size: 20px;
            margin: 0 0 6px;
            color: #f8fafc;
        }

        .subtitle {
            margin: 0 0 12px;
            color: #94a3b8;
        }

        .gags {
            margin-top: 12px;
            color: #9fb0c8;
            font-size: 14px
        }

        .actions {
            margin-top: 18px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap
        }

        .btn {
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            border: none
        }

        .btn-primary {
            background: linear-gradient(90deg, #7c3aed, #2dd4bf);
            color: #071029
        }

        .btn-ghost {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.06);
            color: #cfe8ff
        }

        .small-help {
            margin-top: 14px;
            color: #9fb0c8;
            font-size: 13px
        }

        .fun-emoji {
            font-size: 44px;
        }

        .astro {
            width: 96px;
            height: 96px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
        }

        @media (max-width:700px) {
            .hero {
                flex-direction: column;
                align-items: flex-start
            }

            .code {
                font-size: 48px
            }
        }
    </style>
</head>

<body>
    <div class="error-card">
        <div class="hero">
            <div class="astro" aria-hidden>
                <div class="fun-emoji">👾</div>
            </div>

            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="code">404</div>
                    <div>
                        <div class="title">Whoops — this page went on an adventure.</div>
                        <p class="subtitle">We searched behind the nebula, under the space couch, and even asked a polite alien.</p>
                    </div>
                </div>

                <div class="gags">
                    - Maybe it took a wrong hyperjump.<br>
                    - Maybe it got distracted by shiny CSS.<br>
                    - Or maybe the server hid it as a surprise. You're welcome.
                </div>

                @if(isset($exception) && $exception->getMessage())
                <div class="small-help" style="margin-top:10px;background:rgba(255,255,255,0.02);padding:8px;border-radius:8px;color:#ffdede">{{ $exception->getMessage() }}</div>
                @endif

                <div class="actions">
                    <button onclick="history.back()" class="btn btn-ghost">← Take me back</button>
                </div>

                <div class="small-help">Tip: try searching or check the URL — you might get lucky.</div>
            </div>
        </div>
    </div>
</body>

</html>