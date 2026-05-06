<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Comparative Analysis of Revenue — {{ $period['label'] }}</title>
<style>
    @page { margin: 14mm 12mm; }
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-size: 9.5px;
        color: #1f2937;
        margin: 0;
        padding: 0;
    }
    .title-bar {
        background: #2a4d72;
        color: #fff;
        text-align: center;
        padding: 6px 0;
        font-size: 11px;
        font-weight: bold;
        letter-spacing: 0.04em;
    }
    h1 {
        font-size: 13px;
        text-align: center;
        margin: 12px 0 6px;
        font-weight: 600;
        color: #1f2937;
    }
    .section-banner {
        background: #3a5d82;
        color: #fff;
        text-align: center;
        padding: 5px 0;
        font-size: 10px;
        font-weight: bold;
        letter-spacing: 0.04em;
        margin-top: 14px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0;
    }
    thead th {
        background: #4a6d92;
        color: #fff;
        padding: 5px 6px;
        font-weight: bold;
        font-size: 9px;
        text-align: right;
    }
    thead th:first-child { text-align: left; }
    tbody td {
        padding: 3px 6px;
        border-bottom: 1px solid #e5e7eb;
    }
    tbody td.r { text-align: right; }
    tbody tr:nth-child(even) td { background: #f7f9fc; }
    tr.total td {
        font-weight: bold;
        border-top: 2px solid #4a6d92;
        background: #e8eef6 !important;
    }
    .meta {
        font-size: 8px;
        color: #6b7280;
        text-align: center;
        margin-top: 10px;
    }
</style>
</head>
<body>

@php
    $weekLabel = isset($latestWeek) && $latestWeek !== null
        ? 'Week ' . str_pad((string) $latestWeek, 2, '0', STR_PAD_LEFT)
        : $period['label'];
@endphp

<div class="title-bar">{{ $weekLabel }}</div>
<h1>Comparative Analysis of Revenue</h1>
<p style="text-align:center; font-size:9px; color:#6b7280; margin: 0 0 4px;">{{ $period['label'] }}</p>

@foreach ($sections as $section)
    @php
        $label = $section['name'] === 'Outstream'
            ? 'Outstream Ad Position'
            : ($section['name'] === 'Sticky' ? 'Sticky Bottom Ad Position' : $section['name']);
    @endphp
    <div class="section-banner">{{ $label }}</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                @foreach ($section['sources'] as $src)
                    <th>{{ $src['display_name'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('n/j/Y') }}</td>
                    @foreach ($section['sources'] as $src)
                        <td class="r">
                            @if (isset($row['cells'][$src['key']]))
                                €&nbsp;{{ number_format($row['cells'][$src['key']], 2) }}
                            @else
                                &nbsp;
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endforeach
            <tr class="total">
                <td>Total</td>
                @foreach ($section['sources'] as $src)
                    <td class="r">€&nbsp;{{ number_format($totals[$src['key']] ?? 0, 2) }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
@endforeach

<p class="meta">Generated {{ now()->format('Y-m-d H:i') }}</p>

</body>
</html>
