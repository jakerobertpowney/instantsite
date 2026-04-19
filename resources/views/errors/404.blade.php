<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Page not found | {{ config('app.name', '321Sites') }}</title>
        <style>
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #f8fafc;
                color: #0f172a;
                font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
                padding: 24px;
            }

            main {
                max-width: 560px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 24px;
                padding: 40px;
                box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
            }

            h1 {
                margin: 0 0 12px;
                font-size: clamp(2rem, 4vw, 2.75rem);
            }

            p {
                margin: 0;
                color: #475569;
                line-height: 1.6;
            }

            a {
                display: inline-block;
                margin-top: 24px;
                padding: 12px 18px;
                border-radius: 999px;
                background: #111827;
                color: #ffffff;
                text-decoration: none;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <main>
            <p style="margin-bottom: 10px; font-weight: 600; color: #2563eb;">{{ config('app.name', '321Sites') }}</p>
            <h1>That page could not be found.</h1>
            <p>The link may be out of date, or the page may have moved. Head back to the homepage and we will get you back on track.</p>
            <a href="{{ route('home') }}">Go to homepage</a>
        </main>
    </body>
</html>
