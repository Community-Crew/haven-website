<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Label</title>
    <style>
        /* Recreate your Tailwind styles in plain CSS */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; }
        .label-container {
            display: flex;
            width: 100%; /* Fills the PDF page */
            height: 100%;
            align-items: center;
            justify-content: space-between;
            padding: 8px; /* p-2 */
            background-color: white;
            color: black;
            overflow: hidden;
        }
        .content {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: space-between;
            padding-left: 12px;
            padding-right: 12px;
        }
        .code {
            font-family: monospace;
            font-size: 44px;
            font-weight: 700;
            letter-spacing: 0.05em; /* tracking-wider */
            color: #374151; /* text-gray-800 */
        }
        /* ... etc for the rotated text ... */
    </style>
</head>
<body>
<div class="label-container">
    <div class="content">
        <div class="code">{{ $regCode->code }}</div>
        <!-- ... replicate the rest of your structure ... -->
    </div>
</div>
</body>
</html>
