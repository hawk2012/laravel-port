<!DOCTYPE html>
<html lang="ru" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $code }} — {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
            background: #07080d;
            color: #c3cbdb;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background-image:
                radial-gradient(60% 60% at 50% 0%, rgba(99, 102, 241, 0.22) 0%, transparent 70%),
                linear-gradient(to right, rgba(255,255,255,0.035) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255,255,255,0.035) 1px, transparent 1px);
            background-size: 100% 100%, 56px 56px, 56px 56px;
        }
        .card {
            max-width: 460px;
            width: 100%;
            padding: 32px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            background: linear-gradient(180deg, #10131c, #0b0d14);
            box-shadow: 0 20px 50px -30px rgba(0,0,0,0.9);
            text-align: center;
        }
        .code {
            font-size: 64px;
            line-height: 1;
            font-weight: 600;
            letter-spacing: -0.04em;
            background: linear-gradient(90deg, #8b93ff, #38e0c8);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        h1 { margin: 16px 0 8px; font-size: 18px; color: #fff; font-weight: 600; }
        p { margin: 0 0 24px; font-size: 14px; line-height: 1.6; color: #9aa5bd; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: 150ms;
        }
        .primary { background: #6366f1; color: #fff; }
        .primary:hover { background: #8b93ff; }
        .ghost { border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05); color: #e6eaf3; }
        .ghost:hover { background: rgba(255,255,255,0.1); }
        .brand { display: inline-flex; align-items: center; gap: 10px; margin-bottom: 24px; font-size: 14px; font-weight: 600; color: #fff; }
        .dot { width: 32px; height: 32px; border-radius: 10px; background: #6366f1; display: grid; place-items: center; }
        .dot svg { width: 16px; height: 16px; }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand">
            <span class="dot">
                <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M13 2 4 14h6l-1 8 9-12h-6l1-8Z" />
                </svg>
            </span>
            Kontur AI
        </div>

        <div class="code">{{ $code }}</div>
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>

        <div class="actions">
            <a href="{{ url('/') }}" class="primary">На главную</a>
            @auth
                <a href="{{ url('/dashboard') }}" class="ghost">В дашборд</a>
            @else
                <a href="{{ url('/login') }}" class="ghost">Войти</a>
            @endauth
        </div>
    </main>
</body>
</html>
