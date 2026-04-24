<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }} - README</title>

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif

        {{-- Fallback styles so README is readable even without Vite CSS --}}
        <style>
            :root { color-scheme: light; }
            body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background: #f7f7f7; color: #111; }
            .container { max-width: 980px; margin: 0 auto; padding: 24px 16px; }
            .topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
            .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; }
            a { color: #b91c1c; text-decoration: underline; text-underline-offset: 3px; }
            h1,h2,h3 { margin: 1.1em 0 .4em; line-height: 1.25; }
            h1 { font-size: 1.8rem; margin-top: 0; }
            h2 { font-size: 1.35rem; border-top: 1px solid #eee; padding-top: .8em; }
            h3 { font-size: 1.1rem; }
            p,li { line-height: 1.6; }
            code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-size: .95em; }
            pre { background: #0b1020; color: #e5e7eb; padding: 14px; border-radius: 10px; overflow: auto; }
            pre code { color: inherit; }
            blockquote { margin: 0; padding: 10px 12px; border-left: 4px solid #e5e7eb; background: #fafafa; }
            img { max-width: 100%; height: auto; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="topbar">
                <a href="{{ url('/') }}">
                    ← Back
                </a>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('admin/client-management') }}">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}">
                            Log in
                        </a>
                    @endauth
                @endif
            </div>

            <div class="card">
                {!! $html !!}
            </div>
        </div>
    </body>
</html>

