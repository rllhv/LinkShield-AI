<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
    <meta charset="UTF-8">
    <title>{{ $t['briefing'] }} - {{ $case_id }}</title>
    <style>
        body {
            font-family: 'zain', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #000;
            line-height: 1.2;
            direction: {{ $lang == 'ar' ? 'rtl' : 'ltr' }};
        }

        /* Dossier Elements */
        .confidential-stamp {
            position: absolute;
            top: 15mm;
            {{ $lang == 'ar' ? 'left: 15mm;' : 'right: 15mm;' }}
            border: 3px solid #cc0000;
            color: #cc0000;
            font-weight: bold;
            padding: 5px 12px;
            font-size: 16px;
            text-transform: uppercase;
            transform: rotate({{ $lang == 'ar' ? '-5deg' : '5deg' }});
            opacity: 0.7;
            z-index: 100;
        }

        .header {
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
            /* Ensure header doesn't overlap with stamp */
            margin-top: 15mm;
        }

        .brand {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .document-title {
            font-size: 22px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1px;
            margin: 8px 0;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .meta-table td {
            padding: 4px 0;
            font-size: 14px;
        }

        .label {
            font-weight: bold;
            text-transform: uppercase;
            color: #444;
            width: 110px;
        }

        /* Verdict HUD */
        .verdict-hud {
            border: 2px solid #000;
            background-color: #f8f8f8;
            padding: 12px;
            margin-bottom: 20px;
            width: 100%;
        }

        .score-box {
            float: {{ $lang == 'ar' ? 'right' : 'left' }};
            width: 110px;
            text-align: center;
            border-{{ $lang == 'ar' ? 'left' : 'right' }}: 2px solid #000;
            padding: 5px;
        }

        .score-value {
            font-size: 34px;
            font-weight: bold;
        }

        .score-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #666;
        }

        .verdict-text {
            margin-{{ $lang == 'ar' ? 'right' : 'left' }}: 130px;
            padding: 5px;
        }

        .verdict-status {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Sections */
        .section-title {
            background-color: #000;
            color: #fff;
            padding: 3px 10px;
            font-size: 15px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 10px;
            margin-top: 18px;
            clear: both;
        }

        .technical-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .technical-table th {
            text-align: {{ $lang == 'ar' ? 'right' : 'left' }};
            background-color: #eee;
            padding: 5px;
            border: 1px solid #000;
            font-size: 10px;
            text-transform: uppercase;
        }

        .technical-table td {
            padding: 5px;
            border: 1px solid #000;
            font-size: 13px;
        }

        /* Indicators List */
        .indicator-item {
            padding: 4px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
        }

        .indicator-tag {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            margin-{{ $lang == 'ar' ? 'left' : 'right' }}: 5px;
        }

        .tag-malicious { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }
        .tag-suspicious { background-color: #fef3c7; color: #92400e; border: 1px solid #fbbf24; }

        /* Synthesis */
        .synthesis-box {
            padding: 10px;
            border: 1px dashed #000;
            background-color: #fff;
            font-style: italic;
            font-size: 14px;
        }

        .clear {
            clear: both;
        }
    </style>
</head>
<body>
    <div class="confidential-stamp">{{ $stamp }}</div>

    <div class="header">
        <div class="brand">{{ $t['unit'] }}</div>
        <div class="document-title">{{ $t['briefing'] }}</div>
    </div>

    <table class="meta-table">
        <tr>
            <td class="label">{{ $t['case_id'] }}:</td>
            <td>{{ $case_id }}</td>
            <td class="label">{{ $t['classification'] }}:</td>
            <td>TS // NOFORN</td>
        </tr>
        <tr>
            <td class="label">{{ $t['target_url'] }}:</td>
            <td style="font-family: monospace;">{{ $scan->url }}</td>
            <td class="label">{{ $t['date'] }}:</td>
            <td>{{ $date }}</td>
        </tr>
    </table>

    <div class="verdict-hud">
        <div class="score-box">
            <div class="score-value">{{ $scan->score }}</div>
            <div class="score-label">{{ $t['trust_score'] }}</div>
        </div>
        <div class="verdict-text">
            <div class="verdict-status">
                @if($scan->score >= 80)
                    {{ $t['safe'] }}
                @elseif($scan->score >= 50)
                    {{ $t['suspicious'] }}
                @else
                    {{ $t['malicious'] }}
                @endif
            </div>
            <div style="font-size: 10px; color: #666; margin-top: 3px;">
                {{ $t['verified'] }}
            </div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="section-title">{{ $t['synthesis'] }}</div>
    <div class="synthesis-box">
        {{ $lang == 'ar' ? $aiSummaryAr : $aiSummaryEn }}
    </div>

    <div class="section-title">{{ $t['infra'] }}</div>
    <table class="technical-table">
        <tr>
            <th>{{ $t['parameter'] }}</th>
            <th>{{ $t['value'] }}</th>
        </tr>
        <tr>
            <td>{{ $t['ip'] }}</td>
            <td>{{ $results['engines']['infra']['ip'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>{{ $t['asn'] }}</td>
            <td>{{ $results['engines']['infra']['asnName'] ?? 'N/A' }} ({{ $results['engines']['infra']['asn'] ?? 'N/A' }})</td>
        </tr>
        <tr>
            <td>{{ $t['location'] }}</td>
            <td>{{ $results['engines']['infra']['city'] ?? '' }}, {{ $results['engines']['infra']['country'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>{{ $t['gsb'] }}</td>
            <td>{{ isset($results['engines']['safeBrowsing']) && !empty($results['engines']['safeBrowsing']) ? $t['threat'] : $t['clear'] }}</td>
        </tr>
    </table>

    <div class="section-title">{{ $t['indicators'] }}</div>
    <div style="border: 1px solid #000; padding: 8px;">
        @php $hasIndicators = false; @endphp
        
        @if(isset($results['engines']['virusTotalResults']))
            @foreach($results['engines']['virusTotalResults'] as $engine => $details)
                @if(in_array(strtolower($details['category'] ?? ''), ['malicious', 'suspicious']))
                    <div class="indicator-item">
                        <span class="indicator-tag tag-{{ strtolower($details['category']) }}">{{ $details['category'] }}</span>
                        <strong>{{ $engine }}</strong>: {{ $details['result'] }}
                    </div>
                    @php $hasIndicators = true; @endphp
                @endif
            @endforeach
        @endif

        @if(isset($results['engines']['safeBrowsing']) && is_array($results['engines']['safeBrowsing']))
             @foreach($results['engines']['safeBrowsing'] as $match)
                @if(is_array($match))
                    <div class="indicator-item">
                        <span class="indicator-tag tag-malicious">GOOGLE GSB</span>
                        <strong>{{ $match['threatType'] ?? 'Threat' }}</strong>: {{ $match['platformType'] ?? 'Generic' }}
                    </div>
                    @php $hasIndicators = true; @endphp
                @endif
             @endforeach
        @endif

        @if(!$hasIndicators)
            <div style="padding: 15px; text-align: center; color: #666; font-style: italic;">
                {{ $t['no_indicators'] }}
            </div>
        @endif
    </div>

    <div class="section-title">{{ $t['all_engines'] }}</div>
    <table class="technical-table">
        <tr>
            <th>{{ $t['engine'] }}</th>
            <th>{{ $t['value'] }}</th>
        </tr>
        @if(isset($results['engines']['virusTotalResults']))
            @php $count = 0; @endphp
            @foreach($results['engines']['virusTotalResults'] as $engine => $details)
                @if($count < 40)
                <tr>
                    <td>{{ $engine }}</td>
                    <td style="color: {{ strtolower($details['category'] ?? '') === 'malicious' ? '#b91c1c' : '#059669' }}">
                        {{ strtoupper($details['result'] ?? 'clean') }}
                    </td>
                </tr>
                @endif
                @php $count++; @endphp
            @endforeach
        @endif
    </table>

    <div class="section-title">{{ $t['external'] }}</div>
    <table class="technical-table">
        <tr>
            <th>{{ $t['engine'] }}</th>
            <th>{{ $t['ratio'] }}</th>
            <th>{{ $t['primary'] }}</th>
        </tr>
        <tr>
            <td>VirusTotal Engines</td>
            <td>{{ $results['engines']['virusTotal']['malicious'] ?? 0 }} / {{ ($results['engines']['virusTotal']['malicious'] ?? 0) + ($results['engines']['virusTotal']['harmless'] ?? 0) }}</td>
            <td>{{ isset($results['engines']['virusTotal']['categories']) ? ($results['engines']['virusTotal']['categories'][0] ?? 'N/A') : 'N/A' }}</td>
        </tr>
    </table>

    <div style="margin-top: 30px; border-top: 1px solid #eee; padding-top: 8px; font-size: 10px; color: #999; text-align: center;">
        {{ $t['end'] }} - {{ $date }}
    </div>
</body>
</html>
