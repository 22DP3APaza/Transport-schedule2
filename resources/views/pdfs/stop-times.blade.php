<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $transportType[$language] }} {{ $route->route_short_name }} - {{ $stop->stop_name }} Schedule</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ storage_path('fonts/DejaVuSans.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ storage_path('fonts/DejaVuSans-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 10px;
            font-size: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .route-number {
            font-size: 20px;
            font-weight: bold;
            color: #{{ $route->route_color }};
            margin-bottom: 8px;
        }
        .stop-name {
            font-size: 16px;
            margin-bottom: 15px;
        }
        .schedule-section {
            margin-bottom: 20px;
        }
        .schedule-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            padding: 4px;
            background-color: #f5f5f5;
        }
        .time-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9px;
            table-layout: fixed;
        }
        .time-table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 4px 2px;
            text-align: center;
            font-weight: bold;
            width: 30px;
        }
        .time-table td {
            border: 1px solid #ddd;
            padding: 4px 2px;
            text-align: center;
            vertical-align: top;
            width: 30px;
        }
        .minutes {
            display: block;
            margin-bottom: 2px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
        }
        .time-container {
            line-height: 1.2;
        }
        .stops-page {
            page-break-before: always;
            padding-top: 20px;
        }
        .stops-list {
            margin: 15px 0;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            columns: 2;
            column-gap: 20px;
            font-size: 9px;
            line-height: 1.4;
        }
        .stops-list div {
            break-inside: avoid;
            margin-bottom: 2px;
        }
        .current-stop {
            font-weight: bold;
            color: #{{ $route->route_color }};
        }
        .stops-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }
        .page-footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #666;
            padding: 10px 0;
        }
    </style>
</head>
<body>
    <!-- First Page - Schedule -->
    <div class="header">
        <div class="route-number">{{ $transportType[$language] }} {{ $route->route_short_name }}</div>
        <div class="stop-name">{{ $stop->stop_name }}</div>
        <div>{{ $route->route_long_name }}</div>
    </div>

    @if(count($workdayTimes) > 0)
    <div class="schedule-section">
        <div class="schedule-title">{{ $language === 'lv' ? 'Darbdienas' : 'Workdays' }}</div>
        <table class="time-table">
            <tr>
                @php
                    $hourlyTimes = [];
                    foreach($workdayTimes as $time) {
                        $parts = explode(':', $time);
                        $hour = intval($parts[0]);
                        $minute = $parts[1];
                        if (!isset($hourlyTimes[$hour])) {
                            $hourlyTimes[$hour] = [];
                        }
                        $hourlyTimes[$hour][] = $minute;
                    }
                    ksort($hourlyTimes);
                @endphp
                @foreach($hourlyTimes as $hour => $minutes)
                    <th>{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($hourlyTimes as $hour => $minutes)
                    <td>
                        <div class="time-container">
                            @foreach($minutes as $minute)
                                <span class="minutes">{{ $minute }}</span>
                            @endforeach
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>
    @endif

    @if(count($weekendTimes) > 0)
    <div class="schedule-section">
        <div class="schedule-title">{{ $language === 'lv' ? 'Brīvdienas' : 'Weekends' }}</div>
        <table class="time-table">
            <tr>
                @php
                    $hourlyTimes = [];
                    foreach($weekendTimes as $time) {
                        $parts = explode(':', $time);
                        $hour = intval($parts[0]);
                        $minute = $parts[1];
                        if (!isset($hourlyTimes[$hour])) {
                            $hourlyTimes[$hour] = [];
                        }
                        $hourlyTimes[$hour][] = $minute;
                    }
                    ksort($hourlyTimes);
                @endphp
                @foreach($hourlyTimes as $hour => $minutes)
                    <th>{{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($hourlyTimes as $hour => $minutes)
                    <td>
                        <div class="time-container">
                            @foreach($minutes as $minute)
                                <span class="minutes">{{ $minute }}</span>
                            @endforeach
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    </div>
    @endif

    <div class="page-footer">
        {{ $language === 'lv' ? 'Izveidots' : 'Generated on' }} {{ date('Y-m-d H:i') }}
    </div>

    <!-- Second Page - Stops List -->
    <div class="stops-page">
        <div class="header">
            <div class="route-number">{{ $transportType[$language] }} {{ $route->route_short_name }}</div>
            <div>{{ $route->route_long_name }}</div>
        </div>

        <div class="stops-title">{{ $language === 'lv' ? 'Maršruta pieturas' : 'Route stops' }}:</div>
        <div class="stops-list">
            @foreach($routeStops as $routeStop)
                <div @if($routeStop->stop_id === $stop->stop_id) class="current-stop" @endif>
                    {{ $routeStop->stop_name }}
                </div>
            @endforeach
        </div>

        <div class="page-footer">
            {{ $language === 'lv' ? 'Izveidots' : 'Generated on' }} {{ date('Y-m-d H:i') }}
        </div>
    </div>
</body>
</html>
