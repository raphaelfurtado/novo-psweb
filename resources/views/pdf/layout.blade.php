<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { margin: 0; color: #1f2937; font-size: 11px; }
        .header { border-bottom: 3px solid #1d66f1; padding-bottom: 10px; margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 18px; color: #1d66f1; }
        .header .sub { color: #6b7280; font-size: 12px; margin-top: 2px; }
        .header .periodo { font-size: 12px; font-weight: bold; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th { background: #f3f4f6; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        td { padding: 6px 8px; border-bottom: 1px solid #f3f4f6; }
        .right { text-align: right; }
        .center { text-align: center; }
        .green { color: #059669; }
        .red { color: #dc2626; }
        .blue { color: #1d66f1; }
        tfoot td { font-weight: bold; border-top: 2px solid #d1d5db; background: #fafafa; }
        .cards { width: 100%; margin-bottom: 16px; }
        .cards td { border: none; padding: 4px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; }
        .card .lbl { font-size: 10px; color: #6b7280; }
        .card .val { font-size: 16px; font-weight: bold; margin-top: 2px; }
        .section-title { font-size: 13px; font-weight: bold; margin: 14px 0 6px; }
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 9px; color: #9ca3af; text-align: center; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 9px; background: #e5e7eb; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <div class="sub">@yield('titulo')</div>
        <div class="periodo">{{ $label ?? '' }}</div>
    </div>

    @yield('conteudo')

    <div class="footer">
        Documento gerado em {{ now()->format('d/m/Y H:i') }} · {{ config('app.name') }}
    </div>
</body>
</html>
