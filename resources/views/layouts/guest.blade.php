<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>

        <script>
            // Enter key navigation (Enter moves to next field, last field/button submits)
            document.addEventListener('keydown', function(e) {
                if (e.key !== 'Enter' || e.isComposing || e.defaultPrevented) return;
                const target = e.target;
                if (!target) return;
                const tag = target.tagName ? target.tagName.toLowerCase() : '';
                if (tag === 'button' && target.type !== 'submit') return;
                if (tag === 'a') return;

                const form = target.form || target.closest('form');
                if (!form) return;

                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    const submitBtn = form.querySelector('button[type="submit"]:not([disabled])');
                    if (submitBtn) submitBtn.click();
                    return;
                }

                if (tag === 'button' && target.type === 'submit') return;

                if (tag === 'input') {
                    const type = (target.type || 'text').toLowerCase();
                    if (['button', 'submit', 'reset', 'file'].includes(type)) return;
                } else if (tag !== 'select' && tag !== 'textarea') {
                    return;
                }

                e.preventDefault();
                const selector = 'input:not([type="hidden"]):not([type="button"]):not([type="reset"]), select, textarea, button[type="submit"]';
                const fields = Array.from(form.querySelectorAll(selector)).filter(el => !el.disabled && el.offsetParent !== null);
                const currentIndex = fields.indexOf(target);

                if (e.shiftKey && currentIndex > 0) {
                    fields[currentIndex - 1].focus();
                    return;
                }

                if (currentIndex !== -1 && currentIndex + 1 < fields.length) {
                    fields[currentIndex + 1].focus();
                } else {
                    const submitBtn = form.querySelector('button[type="submit"]:not([disabled])');
                    if (submitBtn) submitBtn.click();
                }
            });
        </script>
    </body>
</html>
