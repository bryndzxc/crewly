<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Memo' }}</title>
    <style>
        @page { margin: 40px 48px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #0f172a; }
        h1, h2, h3 { margin: 0 0 10px 0; }
        p { margin: 0 0 10px 0; line-height: 1.45; }
        .header { margin-bottom: 18px; }
        .title { font-size: 16px; font-weight: 700; }
        .meta { font-size: 10px; color: #475569; margin-top: 4px; }
        .content { border-top: 1px solid #e2e8f0; padding-top: 14px; }
        .footer { position: fixed; bottom: 18px; left: 48px; right: 48px; font-size: 10px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $title ?? 'Memo' }}</div>
        @if(!empty($generatedAt))
            <div class="meta">Generated {{ $generatedAt->format('Y-m-d H:i') }}</div>
        @endif
    </div>

    <div class="content">
        {!! $bodyHtml !!}
    </div>

    @if(!empty($footer))
        <div class="footer">
            {{ $footer }}
        </div>
    @endif
</body>
</html>
