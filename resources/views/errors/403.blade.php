<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 — Zut alors! Forbidden</title>
    <link rel="shortcut icon" href="https://www.planetnine.com/logo/new_favicon.png">
    @vite('resources/css/app.css')
    <style>
        body {
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
            background: linear-gradient(120deg, #081229 0%, #071a2b 60%);
            color: #dbeafe;
            margin: 0;
            padding: 40px 16px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
            border-radius: 14px;
            padding: 22px;
            max-width: 820px;
            width: 100%;
            box-shadow: 0 12px 40px rgba(2, 6, 23, 0.6);
        }

        .row {
            display: flex;
            gap: 18px;
            align-items: center
        }

        .badge {
            font-size: 56px;
            font-weight: 800;
            color: #fca5a5;
        }

        .headline {
            font-size: 18px;
            font-weight: 800;
            margin: 0;
            color: #f8fafc
        }

        .lead {
            margin: 6px 0 0;
            color: #9fb0c8
        }

        .joke {
            margin-top: 12px;
            color: #97b3ce;
            font-size: 14px
        }

        .astro {
            width: 84px;
            height: 84px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
        }

        .astro .emoji {
            font-size: 40px
        }

        .actions {
            margin-top: 16px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap
        }

        .btn {
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 700;
            border: none;
            cursor: pointer
        }

        .btn-ghost {
            background: transparent;
            color: #cfe8ff;
            border: 1px solid rgba(255, 255, 255, 0.04)
        }

        .btn-primary {
            background: linear-gradient(90deg, #fab2c8, #7dd3fc);
            color: #041026
        }

        .small {
            margin-top: 10px;
            color: #9fb0c8;
            font-size: 13px
        }

        @media (max-width:720px) {
            .row {
                flex-direction: column;
                align-items: flex-start
            }

            .badge {
                font-size: 40px
            }
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="row">
            <div class="astro" aria-hidden>
                <div class="emoji">🛑</div>
            </div>

            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="badge">403</div>
                    <div>
                        <p class="headline">Hold up — you shall not pass (this resource).</p>
                        <p class="lead">Our bouncer checked your credentials. They waved politely and said “nope”.</p>
                    </div>
                </div>

                <div class="joke">Possible reasons: you typed the wrong URL, you brought the wrong badge, or the server is being dramatic.</div>

                @if(isset($exception) && $exception->getMessage())
                <div class="small" style="margin-top:10px;background:rgba(255,255,255,0.02);padding:8px;border-radius:8px;color:#ffdede">{{ $exception->getMessage() }}</div>
                @endif

                <div class="actions">
                    <button onclick="history.back()" class="btn btn-ghost">← Go back</button>
                </div>

                <div class="small">Pro tip: If you think this is a bug, send a carrier pigeon to support (or just email).</div>
            </div>
        </div>
    </div>
</body>

</html>