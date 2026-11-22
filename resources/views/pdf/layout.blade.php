<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 18mm 15mm;
        }

        body {
            color: #1f2937;
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10px;
            line-height: 1.45;
        }

        .store-header {
            width: 100%;
            border-bottom: 2px solid #1f2937;
            margin-bottom: 18px;
            padding-bottom: 10px;
        }

        .store-header td {
            vertical-align: middle;
        }

        .store-logo {
            max-height: 55px;
            max-width: 90px;
        }

        .store-name {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }

        .store-address {
            margin: 3px 0 0;
            white-space: pre-line;
        }

        .document-title {
            margin: 0 0 12px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .meta,
        .items,
        .totals {
            width: 100%;
            border-collapse: collapse;
        }

        .meta {
            margin-bottom: 14px;
        }

        .meta td {
            padding: 2px 0;
            vertical-align: top;
        }

        .items th,
        .items td {
            border: 1px solid #9ca3af;
            padding: 6px;
        }

        .items th {
            background: #f3f4f6;
            text-align: left;
        }

        .items .right,
        .totals .right {
            text-align: right;
            white-space: nowrap;
        }

        .totals {
            margin-top: 12px;
            margin-left: auto;
            width: 52%;
        }

        .totals td {
            padding: 3px 0 3px 8px;
        }

        .totals .grand-total td {
            border-top: 1px solid #1f2937;
            font-size: 12px;
            font-weight: bold;
            padding-top: 6px;
        }

        .notes {
            margin-top: 16px;
            white-space: pre-line;
        }

        .watermark {
            position: fixed;
            top: 42%;
            left: 0;
            width: 100%;
            color: #e5e7eb;
            font-size: 64px;
            font-weight: bold;
            text-align: center;
            z-index: 100;
        }

        .receipt {
            font-size: 8px;
        }

        .receipt .store-header {
            margin-bottom: 10px;
            padding-bottom: 7px;
        }

        .receipt .store-name {
            font-size: 13px;
        }

        .receipt .store-logo {
            max-height: 38px;
            max-width: 58px;
        }

        .receipt .document-title {
            margin-bottom: 8px;
            font-size: 12px;
        }

        .receipt .meta {
            margin-bottom: 8px;
        }

        .receipt .items th,
        .receipt .items td {
            border-left: 0;
            border-right: 0;
            padding: 4px 2px;
        }

        .receipt .totals {
            width: 100%;
            margin-top: 8px;
        }

        .receipt .watermark {
            top: 40%;
            font-size: 42px;
        }
    </style>
    @yield('page_styles')
</head>
<body class="@yield('body_class')">
    @if ($isCancelled)
        <div class="watermark">BATAL</div>
    @endif
    @include('pdf.partials.store-header')
    @yield('document')
</body>
</html>
