<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Video Code Batch Export</title>
    <style>
        @page {
            margin: 8mm;
        }

        body {
            color: #111827;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.15;
            margin: 0;
        }

        .page {
            page-break-after: always;
            page-break-inside: avoid;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .header {
            border-bottom: 1px solid #d1d5db;
            margin-bottom: 4mm;
            padding-bottom: 3mm;
        }

        .title {
            font-size: 15px;
            font-weight: bold;
            margin: 0 0 1.5mm;
        }

        .subtitle {
            color: #4b5563;
            margin: 0 0 1mm;
        }

        .meta-table {
            border-collapse: collapse;
            margin: 0 0 4mm;
            width: 100%;
        }

        .meta-table td {
            border: 1px solid #e5e7eb;
            padding: 3px 5px;
            font-size: 8px;
            width: 50%;
        }

        .cards-table {
            border-collapse: separate;
            border-spacing: 0 3mm;
            table-layout: fixed;
            width: 100%;
        }

        .cards-row {
            page-break-inside: avoid;
        }

        .card-cell {
            padding-right: 3mm;
            vertical-align: top;
            width: 50%;
        }

        .card-cell:last-child {
            padding-right: 0;
        }

        .card {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            box-sizing: border-box;
            height: {{ $layout['card_height_mm'] }}mm;
            overflow: hidden;
            page-break-inside: avoid;
            padding: 3.5mm 4mm;
        }

        .sequence {
            color: #6b7280;
            font-size: 8px;
            margin-bottom: 1.5mm;
        }

        .qr {
            display: block;
            height: {{ $layout['qr_display_mm'] }}mm;
            margin: 0 auto 2mm;
            width: {{ $layout['qr_display_mm'] }}mm;
        }

        .code {
            font-size: {{ $layout['code_font_px'] }}px;
            font-weight: bold;
            letter-spacing: 0.3px;
            margin-bottom: 1.5mm;
            text-align: center;
        }

        .meta {
            color: #374151;
            font-size: {{ $layout['meta_font_px'] }}px;
            margin: 0.5mm 0 0;
            text-align: center;
        }
    </style>
</head>
<body>
@foreach ($pages as $page)
    <div class="page">
        <div class="header">
            <p class="title">Video Access Codes</p>
            <p class="subtitle">Batch: {{ $batch->batch_code }}</p>
            <p class="subtitle">Course: {{ $courseTitle }}</p>
            <p class="subtitle">Video: {{ $videoTitle }}</p>
            <p class="subtitle">Codes: {{ $rangeLabel }} ({{ $exportedCount }} total)</p>
        </div>

        <table class="meta-table">
            <tr>
                <td><strong>Total Codes</strong><br>{{ $batch->quantity }}</td>
                <td><strong>View Limit Per Code</strong><br>{{ $batch->view_limit_per_code }}</td>
            </tr>
            <tr>
                <td><strong>Status</strong><br>{{ $batch->status->name }}</td>
                <td><strong>Generated At</strong><br>{{ $batch->generated_at?->format('Y-m-d H:i') }}</td>
            </tr>
        </table>

        <table class="cards-table">
            @foreach (array_chunk($page, 2) as $row)
                <tr class="cards-row">
                    @foreach ($row as $item)
                        <td class="card-cell">
                            <div class="card">
                                <div class="sequence">Code #{{ $item['sequence'] }}</div>
                                <img class="qr" src="{{ $item['qr_code_data_url'] }}" alt="QR code for {{ $item['code'] }}">
                                <div class="code">{{ $item['code'] }}</div>
                                <p class="meta">Course: {{ $cardCourseTitle }}</p>
                                <p class="meta">Video: {{ $cardVideoTitle }}</p>
                                <p class="meta">Views: {{ $batch->view_limit_per_code }}</p>
                            </div>
                        </td>
                    @endforeach
                    @if (count($row) === 1)
                        <td class="card-cell"></td>
                    @endif
                </tr>
            @endforeach
        </table>
    </div>
@endforeach
</body>
</html>
