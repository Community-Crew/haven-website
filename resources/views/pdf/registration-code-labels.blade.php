<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registration Code Labels</title>
    <style>
        {{-- Brother QL-700 with DK-11201 die-cut labels: 29mm x 90mm. Absolute
             positioning (rather than table/flex layout) is deliberate - dompdf's
             table layout sizes replaced elements (the QR <img>) using their
             intrinsic pixel dimensions before applying the CSS width/height,
             which overflowed the 29mm page height and spilled each label onto
             an extra, mostly-blank page. --}}
        @page {
            margin: 0;
            size: 90mm 29mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "DejaVu Sans", sans-serif;
        }

        .label {
            position: relative;
            width: 90mm;
            height: 29mm;
            overflow: hidden;
        }

        .label.break {
            page-break-after: always;
        }

        .qr {
            position: absolute;
            top: 3.5mm;
            left: 3mm;
            width: 22mm;
            height: 22mm;
        }

        .text {
            position: absolute;
            top: 3mm;
            left: 28mm;
            right: 3mm;
        }

        .unit-name {
            font-size: 11pt;
            font-weight: bold;
            margin: 0 0 1mm;
        }

        .code {
            font-size: 15pt;
            font-weight: bold;
            letter-spacing: 1pt;
            font-family: "DejaVu Sans Mono", monospace;
            margin: 0;
        }

        .hint {
            font-size: 6.5pt;
            color: #444444;
            margin: 1mm 0 0;
        }
    </style>
</head>
<body>
    @foreach ($labels as $label)
        <div class="label @unless ($loop->last) break @endunless">
            <img class="qr" src="{{ $label['qr'] }}" alt="QR code">
            <div class="text">
                <p class="unit-name">{{ $label['unit_name'] }}</p>
                <p class="code">{{ $label['code'] }}</p>
                <p class="hint">Scan or go to {{ $label['host'] }} to activate</p>
            </div>
        </div>
    @endforeach
</body>
</html>
