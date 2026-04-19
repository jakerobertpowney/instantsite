<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Something went wrong | {{ config('app.name', '321Sites') }}</title>
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #020617;
                color: #e2e8f0;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                padding: 24px;
            }

            main {
                max-width: 560px;
                background: rgba(15, 23, 42, 0.92);
                border: 1px solid rgba(148, 163, 184, 0.24);
                border-radius: 24px;
                padding: 40px;
                box-shadow: 0 24px 60px rgba(2, 6, 23, 0.45);
            }

            h1 {
                margin: 0 0 12px;
                font-size: clamp(2rem, 4vw, 2.75rem);
            }

            p {
                margin: 0;
                color: #cbd5e1;
                line-height: 1.6;
            }

            a {
                display: inline-block;
                margin-top: 24px;
                padding: 12px 18px;
                border-radius: 999px;
                background: #ffffff;
                color: #0f172a;
                text-decoration: none;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <main>
            <p style="margin-bottom: 10px; font-weight: 600; color: #93c5fd;">{{ config('app.name', '321Sites') }}</p>
            <h1>Something went wrong on our side.</h1>
            <p>Please try again in a moment. If the problem keeps happening, head back to the homepage and try again from there.</p>
            <a href="{{ route('home') }}">Back to homepage</a>
        </main>
    </body>
</html>
