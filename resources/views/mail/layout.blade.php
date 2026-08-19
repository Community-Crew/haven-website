<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="m-0 p-0 bg-haven-white font-sans text-haven-black">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="bg-haven-white py-8">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="max-w-[480px] bg-white overflow-hidden shadow-[4px_4px_0px_0px_#091d4b] rounded-2xl border-2 border-solid border-haven-blue">
                    <tr>
                        <td class="bg-haven-blue pt-6 px-8 text-xl font-bold text-haven-yellow">
                            Haven Community
                        </td>
                    </tr>
                    <tr>
                        <td class="bg-white leading-[0]">
                            <svg viewBox="0 0 1440 110" width="100%" height="44" preserveAspectRatio="none" style="display:block">
                                <path class="fill-haven-blue" d="M1440,55L1416,51.3C1392,48,1344,40,1296,36.7C1248,33,1200,33,1152,31.2C1104,29,1056,26,1008,38.5C960,51,912,81,864,91.7C816,103,768,95,720,91.7C672,88,624,88,576,89.8C528,92,480,95,432,82.5C384,70,336,40,288,38.5C240,37,192,62,144,73.3C96,84,48,81,0,71.5L0,0L1440,0Z" />
                            </svg>
                        </td>
                    </tr>
                    <tr>
                        <td class="p-8 text-[15px] leading-relaxed">
                            @yield('content')
                        </td>
                    </tr>
                </table>
                <p class="text-xs text-gray-500 mt-4">{{ __('mail.footer') }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
