<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Abel&display=swap" rel="stylesheet">
    <title>Australian Scorebook - Game {{ $game->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            size: A3 landscape;
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            padding: 0;
            margin: 0;
        }

        .scorebook {
            width: 100%;
            border: 2px solid #000;
            page-break-inside: avoid;
        }

        /* Header Section */
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #000;
            background-color: #fff;
        }

        .header-row {
            display: table-row;
        }

        .header-cell {
            display: table-cell;
            padding: 4px;
            border-right: 1px solid #000;
            font-size: 10pt;
        }

        .abel-regular {
            font-family: "Abel", sans-serif;
            font-weight: 400;
            font-style: normal;
        }

        .header-cell:last-child {
            border-right: none;
        }

        .team-names {
            width: 100%;
            border-bottom: 2px solid #000;
            font-size: 12pt;
            font-weight: bold;
            display: flex;
            flex-direction: row;
            column-gap: 20px;
            padding: 8px;
        }

        .team-names div {
            /* flex: 0; */
            text-align: center;
        }

        .team-names div:first-child {
            text-align: left;
        }

        .team-names div:last-child {
            text-align: right;
        }

        .team-home {
            text-decoration: underline;
        }

        /* Game Notes Section */
        .game-notes {
            position: absolute;
            top: 10px;
            right: 10px;
            border: 2px solid #000;
            padding: 5px;
            width: 200px;
            background: white;
        }

        /* tr.hitter-row {
            height: 56px;
        } */

        /* Ensure consistent heights for fielding header rows */
        .main-grid thead tr {
            height: auto;
        }

        .main-grid thead tr th.main-stats-header {
            height: 10px;
            line-height: 10px;
        }

        .game-notes-title {
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding: 2px;
            margin-bottom: 5px;
        }

        .game-notes-content {
            min-height: 60px;
            font-size: 7pt;
        }

        /* Main Grid */
        .main-grid {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .main-grid th, .main-grid td {
            border: 1px solid #000;
            vertical-align: middle;
        }

        /* Narrow spacing columns */
        .main-grid td.spacing-col,
        .main-grid th.spacing-col {
            width: 8px;
            min-width: 8px;
            max-width: 8px;
            padding: 0;
        }

        /* Fielding Section */
        .fielding-section {
            width: 80px;
        }

        .fielding-header {
            padding: 4px;
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
            background-color: #f0f0f0;
        }

        .fielding-row {
            width: 100%;
            height: 35px;
            border-bottom: 1px solid #000;
        }

        .fielding-row table {
            width: 100%;
            border-collapse: collapse;
            height: 100%;
        }

        .fielding-row td {
            border: none;
            padding: 2px;
            font-size: 7pt;
        }

        .fielding-pos {
            width: 20px;
            border-right: 1px solid #000;
            text-align: center;
        }

        .fielding-player {
            text-align: left;
        }

        /* Batting Order Section */
        .batting-section {
            width: 120px;
        }

        .batting-header {
            padding: 4px;
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
            background-color: #f0f0f0;
        }

        .batting-row {
            width: 100%;
            height: 35px;
            border-bottom: 1px solid #000;
        }

        .batting-row table {
            width: 100%;
            border-collapse: collapse;
            height: 100%;
        }

        .batting-row td {
            border: none;
            padding: 2px;
            font-size: 7pt;
        }

        .batting-number {
            width: 15px;
            border-right: 1px solid #000;
            text-align: center;
        }

        .batting-jersey {
            width: 20px;
            border-right: 1px solid #000;
            text-align: center;
            color: red;
        }

        .main-grid td.batting-name {
            text-align: left;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            padding-left: 5px;
            max-width: 80px;
            font-size: 7pt;
        }

        /* Inning Columns */
        .inning-column {
            width: 30px;
        }

        .inning-header {
            text-align: center;
            padding: 2px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            background-color: #f0f0f0;
            font-size: 7pt;
            height: 100%;
            width: 60px;
        }

        .inning-runs {
            text-align: center;
            padding: 2px;
            border-bottom: 1px solid #000;
            font-size: 7pt;
            height: 15px;
        }

        .inning-cell {
            height: 45px;
            border-bottom: 1px solid #000;
            position: relative;
            padding: 0;
            text-align: center;
        }

        /* Play cell with 4 rectangles and circle */
        .play-cell {
            width: 100%;
            height: 100%;
            position: relative;
            border-collapse: collapse;
        }
        .play-quadrant-table {
            border-collapse: collapse;
            position: relative;
            height: 100%;
        }

        .play-quadrant {
            width: 30px;
            height: 20.5px;
            border: 0.5px solid #000;
            font-size: 5pt;
            text-align: center;
            vertical-align: middle;
        }

        .play-quadrant-table tr:first-of-type td {
            border-top: none;
        }

        .play-quadrant-table tr:last-of-type td {
            border-bottom: none;
        }

        .play-quadrant-table tr td:first-of-type {
            border-left: none;
        }

        .play-quadrant-table tr td:last-of-type {
            border-right: none;
        }

        .run-circle {
            width: 13px;
            height: 13px;
            border: 1px solid #000;
            border-radius: 50%;
            position: absolute;
            top: 14px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            font-size: 7pt;
            font-weight: bold;
            color: #ff6600;
            line-height: 13px;
            text-align: center;
        }

        .play-quadrant.play-blue {
            color: blue;
            background-color: rgb(155, 226, 255);
            font-weight: bold;
        }

        .play-quadrant.play-blue-text {
            color: blue;
        }

        .play-quadrant.play-red-text {
            color: red;
        }

        .play-quadrant.play-green {
            color: green;
            background-color: #ccffcc;
            font-weight: bold;
        }

        .play-quadrant.play-red {
            color: red;
            background-color: #ffcccc;
            font-weight: bold;
        }

        .play-quadrant .play-circled {
            border: 1px solid #000;
            border-radius: 50%;
            padding: 1px 1px;
            font-size: 6pt;
            line-height: 1;
            font-weight: bold;
        }

        .play-quadrant-table .play-blue-text span.play-circled {
            border-color: blue;
        }

        .play-quadrant-table .play-red-text span.play-circled, .play-quadrant-table .play-red span.play-circled {
            border-color: red;
        }

        .play-quadrant-table tr:first-of-type td.pinch-runner:first-of-type {
            border-bottom: 2px solid #00b000;
        }

        .play-quadrant-table tr:first-of-type td.pinch-runner:last-of-type {
            border-left: 2px solid #00b000;
        }

        .play-quadrant-table tr:nth-of-type(2) td:last-of-type.pinch-runner {
            border-top: 2px solid #00b000;
        }

        .run-circle.earned {
            background: #88ff88;
        }

        .run-circle.unearned {
            background: #ff0000;
        }

        .diagonal-line, .diamond-up-left, .diamond-up-right, .diamond-down-left, .diamond-down-right {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        }

         .diamond-up-left::after, .diamond-up-right::after, .diamond-down-left::after, .diamond-down-right::after {
            content: '';
            position: absolute;
            height: 3px;
            background-color: #ffaa0088;
         }

        .diagonal-line::after {
            content: '';
            position: absolute;
            height: 3px;
            background-color: #ffaa00;
            width: calc(hypot(60px, 56px));
            bottom: -3px;
            left: -1px;
            transform: rotate(calc(atan(-56 / 60)));
            transform-origin: top left;
        }

        .diamond-up-left::after {
            bottom: -3px;
            left: -1px;
            width: calc(hypot(30px, 56px));
            transform: rotate(calc(atan(-56 / 30)));
            transform-origin: top left;
        }

        .diamond-up-right::after {
            bottom: -3px;
            right: -1px;
            width: calc(hypot(30px, 56px));
            transform: rotate(calc(atan(56 / 30)));
            transform-origin: top right;
        }

        .diamond-down-left::after {
            top: -3px;
            left: -1px;
            width: calc(hypot(30px, 56px));
            transform: rotate(calc(atan(56 / 30)));
            transform-origin: bottom left;
        }

        .diamond-down-right::after {
            top: -3px;
            right: -1px;
            width: calc(hypot(30px, 56px));
            transform: rotate(calc(atan(-56 / 30)));
            transform-origin: bottom right;
        }

        .inning-fielding {
            text-align: left;
            font-family: monospace;
        }

        .inning-cell.inning-start {
            border-top: 2px solid #ffaa00 !important;
        }

        .inning-cell.inning-start table.play-quadrant-table tr:first-of-type td {
            border-top: 1px solid #ffaa00 !important;
        }

        .inning-cell.pitcher-change {
            border-top: 2px solid blue !important;
        }

        .inning-cell.pitcher-change table.play-quadrant-table tr:first-of-type .play-quadrant, .inning-cell.inning-start table.play-quadrant-table tr:first-of-type .play-quadrant {
            height: 20px;
        }

        .inning-cell.pitcher-change table.play-quadrant-table tr:first-of-type td {
            border-top: 1px solid blue !important;
        }

        .inning-cell.next-at-bat {
            border-left: 3px solid #00b000 !important;
        }

        .play-quadrant-table .pitch-sequence {
            font-size: 6pt;
            text-align: left;
            padding-left: 2px;
            border-right: none;
            text-transform: uppercase;
            font-family: monospace;
        }

        .play-quadrant-table .pitch-total {
            font-size: 6pt;
            text-align: right;
            padding-right: 1px;
            border-left: none;
        }

        /* Statistics Section */
        .stats-section {
            width: 150px;
        }

        .stats-header {
            padding: 4px;
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
            background-color: #f0f0f0;
        }

        .stats-row {
            width: 100%;
            height: 35px;
            border-bottom: 1px solid #000;
            font-size: 6pt;
        }

        .stats-row table {
            width: 100%;
            border-collapse: collapse;
            height: 100%;
        }

        .stats-row td {
            border: none;
            border-right: 1px solid #000;
            text-align: center;
            padding: 2px;
            width: 5.5%; /* Uniform width for each stat column */
        }

        .stats-row td:last-child {
            border-right: none;
        }

        /* Fielding stats columns */
        .main-grid td {
            text-align: center;
            font-size: 7pt;
        }

        /* Bottom sections */
        .pitcher-innings-table {
            width: 100%;
            border-collapse: collapse;
            border-top: 2px solid #000;
            font-size: 7pt;
        }

        .pitcher-innings-table td {
            border: 1px solid #000;
            padding: 2px;
            vertical-align: middle;
        }

        .pitcher-inning-cell {
            width: 30px;
            text-align: center;
        }

        .bottom-section {
            width: 100%;
            border-top: 2px solid #000;
        }

        .bottom-section table {
            width: 100%;
            border-collapse: collapse;
        }

        .bottom-section td {
            vertical-align: top;
            padding: 5px;
        }

        .pitchers-section {
            border-right: 2px solid #000;
        }

        .totals-section {
            width: 300px;
        }

        .section-title {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding: 2px;
            margin-bottom: 4px;
        }

        table.summary {
            width: 100%;
            border-collapse: collapse;
            font-size: 7pt;
            table-layout: fixed;
        }

        table.summary td {
            border: 1px solid #000;
            padding: 1px 2px;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        table.summary td strong {
            font-size: 7pt;
        }

        /* Pitcher stats table - narrow columns */
        .section-title + table.summary td:not(:first-child) {
            width: 25px;
            text-align: center;
        }

        .section-title + table.summary td:first-child {
            width: auto;
        }

        /* Venue information */
        .venue-info {
            padding: 4px 8px;
            border-bottom: 1px solid #000;
            font-size: 9pt;
        }

        /* Print optimizations */
        @media print {
            body {
                padding: 0;
            }

            .scorebook {
                page-break-inside: avoid;
            }

            .main-grid {
                page-break-inside: avoid;
            }

            .main-grid tbody {
                page-break-inside: avoid;
            }
        }

        /* PDF-specific optimizations */
        .scorebook {
            max-width: 100%;
        }

        /* Prevent page breaks */
        .main-grid, .main-grid tbody, .main-grid tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Notes section for unclear notations */
        .notation-notes {
            margin-top: 10px;
            padding: 5px;
            border: 1px solid #666;
            background-color: #fffef0;
            font-size: 7pt;
        }

        .notation-notes h4 {
            font-size: 8pt;
            margin-bottom: 3px;
        }

        .notation-notes ul {
            margin-left: 15px;
        }

        table th.main-stats-header {
            width: 16px;
            font-size: 5pt;
            text-align: center;
            vertical-align: middle;
        }

        .non-ab-stat {
            background-color: #f0f0f0;
        }

        td.batting-number {
            width: 20px;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            font-size: 9pt;
        }

        .inning-fielding table.fielding-stats-row td {
            font-size: 6pt;
            border: none;
            line-height: normal;
        }

        .inning-fielding {
            position: relative;
            border-collapse: collapse;
        }

        .inning-fielding > div {
            width: 100%;
            height: 100%;
            position: relative;
            border-collapse: collapse;
        }

        .inning-fielding table.fielding-stats-row {
            position: relative;
            border-collapse: collapse;
            width: 100%;
            height: 100%;
            border: none;
            border-spacing: 0;
        }

        table.pitcher-stats-subtable {
            width: 100%;
            border: none;
            border-collapse: collapse;
        }
        table.pitcher-stats-subtable td {
            border: none !important;
        }
        table.pitcher-stats-subtable tr {
            border-bottom: 1px solid black;
        }
        /* table.pitcher-stats-subtable tr:last-of-type {
            border-bottom: none;
        } */

        .pitcher-name td {
            text-align: left;
            padding-left: 6px;
        }

        tr.hitter-row.hitter-divider > td {
            border-top: 2px solid black;
        }

        .player-name {
            font-size: 10pt !important;
        }

        .pitcher-stat, .catcher-stat, .totals-stat {
            font-size: 8pt !important;
            text-align: center;
            vertical-align: middle !important;
            padding: 0px !important;
        }
    </style>
</head>
<body>
    <div class="scorebook">
        <!-- Team Names -->
        <div class="team-names">
            <div class="{{ $isHome ? 'team-home' : '' }}">{{ $isHome ? $team->name : $opponent->name }} (HOME)</div>
            <div>V</div>
            <div class="{{ !$isHome ? 'team-home' : '' }}">
                {{ $isHome ? $opponent->name : $team->name }} (AWAY)
            </div>
        </div>

        <!-- Venue Information -->
        <div class="venue-info">
            <strong>VENUE:</strong> {{ $venue }} &nbsp;&nbsp;
            <strong>DATE:</strong> {{ $date }} &nbsp;&nbsp;
            <strong>TIME START:</strong> {{ $timeStart }} &nbsp;&nbsp;
            <strong>FINISH:</strong> {{ $timeFinish }} &nbsp;&nbsp;
            <strong>TOTAL:</strong> {{ $totalTime }}
        </div>

        <!-- Main Grid -->
        <table class="main-grid">
            <!-- Header Row -->
            <thead>
                <tr>
                    <th rowspan="3" colspan="8" class="fielding-section fielding-header">FIELDING</th>
                    <th rowspan="1" colspan="2" class="batting-section batting-header">BATTING ORDER</th>
                    <th>&nbsp;</th>
                    @foreach($innings as $inning)
                    @for($i = 0; $i < $inning['width']; $i++)
                    <th class="inning-column inning-header">{{ $inning['number'] }}</th>
                    @endfor
                    @endforeach
                    <th rowspan="3" colspan="20" class="stats-section stats-header">BATTING</th>
                </tr>
                <tr>
                    <!-- Batting Order Sub-Header -->
                    <th rowspan="3" colspan="2" class="batting-section">
                        <div style="padding: 4px; text-align: center; font-weight: bold; height: 15px;">TEAM: {{ $team->name }}</div>
                    </th>
                    <!-- Assist row for each inning -->
                    <th class="main-stats-header">A</th>
                    @foreach($innings as $inning)
                    <td class="inning-fielding" colspan="{{ $inning['width'] }}">
                        <div>
                            <table class="fielding-stats-row"><tr>
                                <td>
                                    @foreach ($inning['fielding'] as $fielding)
                                        @if ($fielding['A'] ?? false)
                                            {{ $fielding['A'] }}
                                        @elseif (isset($fielding['PC']))
                                            </td><td style="border-left: 2px solid blue;">
                                        @elseif (isset($fielding['DC']))
                                            </td><td style="border-left: 2px solid orange;">
                                        @else
                                            &nbsp;
                                        @endif
                                    @endforeach
                                </td>
                            </tr></table>
                        </div>
                    </td>
                    @endforeach
                </tr>
                <tr>
                    <!-- Putout row for each inning -->
                    <th class="main-stats-header">PO</th>
                    @foreach($innings as $inning)
                    <td class="inning-fielding" colspan="{{ $inning['width'] }}">
                        <div>
                            <table class="fielding-stats-row"><tr>
                                <td>
                                    @foreach ($inning['fielding'] as $fielding)
                                        @for($i = 1; $i < strlen($fielding['A'] ?? ' '); $i++)
                                            &nbsp;
                                        @endfor
                                        @if (isset($fielding['PO']))
                                            {{ $fielding['PO'] }}
                                        @elseif (isset($fielding['PC']))
                                            </td><td style="border-left: 2px solid blue;">
                                        @elseif (isset($fielding['DC']))
                                            </td><td style="border-left: 2px solid orange;">
                                        @else
                                            &nbsp;
                                        @endif
                                    @endforeach
                                </td>
                            </tr></table>
                        </div>
                    </td>
                    @endforeach
                </tr>
                <tr>
                    <th class="main-stats-header">DO</th>
                    <th class="spacing-col">&nbsp;</th>
                    <th class="main-stats-header">PO</th>
                    <th class="main-stats-header">A</th>
                    <th class="main-stats-header">E</th>
                    <th class="spacing-col">&nbsp;</th>
                    <th class="main-stats-header">Pos</th>
                    <th class="main-stats-header">Ch</th>
                    <!-- Error row for each inning -->
                    <th class="main-stats-header">E</th>
                    @foreach($innings as $inning)
                    <td class="inning-fielding" colspan="{{ $inning['width'] }}">
                        <div>
                            <table class="fielding-stats-row"><tr>
                                <td>
                                    @foreach ($inning['fielding'] as $fielding)
                                        @for($i = 1; $i < strlen($fielding['A'] ?? ' '); $i++)
                                            &nbsp;
                                        @endfor
                                        @if (isset($fielding['E']))
                                            <span style="color:red">{{ $fielding['E'] }}</span>
                                        @elseif (isset($fielding['PC']))
                                            </td><td style="border-left: 2px solid blue;">
                                        @elseif (isset($fielding['DC']))
                                            </td><td style="border-left: 2px solid orange;">
                                        @else
                                            &nbsp;
                                        @endif
                                    @endforeach
                                </td>
                            </tr></table>
                        </div>
                    </td>
                    @endforeach

                    <!-- Hitting Stats Headers -->
                    <th class="main-stats-header">PA</th>
                    <th class="main-stats-header">AB</th>
                    <th class="main-stats-header">R</th>
                    <th class="main-stats-header">H</th>
                    <th class="main-stats-header">2</th>
                    <th class="main-stats-header">3</th>
                    <th class="main-stats-header">HR</th>
                    <th class="main-stats-header">RBI</th>
                    <th class="main-stats-header non-ab-stat">SAB</th>
                    <th class="main-stats-header non-ab-stat">SAF</th>
                    <th class="main-stats-header non-ab-stat">BB</th>
                    <th class="main-stats-header non-ab-stat">HBP</th>
                    <th class="main-stats-header non-ab-stat">CI</th>
                    <th class="main-stats-header">K</th>
                    <th class="main-stats-header">GDP</th>
                    <th class="main-stats-header">SB</th>
                    <th class="main-stats-header">CS</th>
                    <th class="main-stats-header">LOB</th>
                </tr>
            </thead>
            <!-- Totals/Summary Row -->
            <tbody>
                <!-- Batter Rows -->
                @php
                $groupedBatters = collect($battingOrder)->groupBy('spot');
                $teamStats = new \App\Helpers\StatsHelper([]);
                @endphp
                @foreach($groupedBatters as $spot => $batters)
                @if ($spot === 'P')
                    @continue
                @endif
                @php $rowspan = count($batters); @endphp
                @foreach($batters->reverse() as $index => $batter)
                @php
                $stats = (new \App\Helpers\StatsHelper($batter['player']->stats ?? []));
                $teamStats->merge($stats);
                @endphp
                <tr @class(['hitter-row', 'hitter-divider' => $loop->first])>
                    <td style="text-align: center;">{{ $stats->DO }}</td>
                    <td class="spacing-col">&nbsp;</td>
                    <td style="text-align: center;">{{ $stats->PO }}</td>
                    <td style="text-align: center;">{{ $stats->A }}</td>
                    <td style="text-align: center;">{{ $stats->E }}</td>
                    <td class="spacing-col">&nbsp;</td>
                    <td class="fielding-player">
                        @foreach ($batter['positions'] as [$inning, $outs, $position])
                            @if ($loop->first && $loop->parent->first)
                                {{ $position }}<br/>
                            @else
                                <span style="text-decoration:line-through">{{ $position }}</span><br/>
                            @endif
                        @endforeach
                    </td>
                    <td class="fielding-pos">
                        @foreach ($batter['positions'] as [$inning, $outs, $position, $half])
                            @if ($inning !== 1 || $outs !== 0)
                                <span @style(["text-decoration:overline" => !$half, 'text-decoration:underline' => $half])>{{ $inning }}@if($outs).{{ $outs }}@endif</span><br/>
                            @else
                                &nbsp;<br/>
                            @endif
                        @endforeach
                    </td>

                    <!-- Batter name and jersey number -->
                    <td class="batting-name player-name">
                        @if ($loop->first)
                            {{ $batter['name'] }}
                        @else
                            <span style="text-decoration: line-through;">{{ $batter['name'] }}</span>
                        @endif
                    </td>
                    <td class="batting-jersey">{{ $batter['number'] }}</td>
                    @if ($loop->first)
                    <td class="batting-number" rowspan="{{ $rowspan }}">{{ $spot }}</td>
                    @endif

                    <!-- Plate appearance cells. -->
                    @if ($loop->first)
                    @foreach($innings as $inning)
                    @for($i = 0; $i < $inning['width']; $i++)
                    @php
                    $play = ($batterInningData[$batter['spot']] ?? [])[$inning['number']][$i] ?? null;
                    $lastPitch = strtoupper(substr($play?->pitches ?? '', -1));
                    $lastPitch = $lastPitch === 'S' ? '2' : $lastPitch;
                    $bigK = ($play?->results[0][0] ?? '') === 'K2';
                    $outNumber = $play?->out_number ?? null;
                    $runEarned = $play?->run_earned ?? null;
                    $inningEnd = $play?->inning_end ?? false;
                    $inningStart = $play?->inning_start ?? false;
                    $diamondUp = $play?->diamondUp ?? false;
                    $diamondDown = $play?->diamondDown ?? false;
                    $pitches = $play->pitches ?? '';
                    if ($play->results[0] ?? null) {
                        $pitches = substr($pitches, 0, -1);
                    }
                    $pitches = str_replace('.', '&middot;', $pitches);
                    @endphp
                    <td rowspan="{{ $loop->parent->count }}" @class([
                        'inning-cell',
                        'inning-end' => $inningEnd,
                        'inning-start' => $inningStart,
                        'pitcher-change' => $play?->pitcher_change ?? false,
                        'next-at-bat' => $play?->next_at_bat ?? false,
                    ])>
                        <!-- Play cell with 4 quadrants and circle -->
                        <div class="play-cell">
                            @if($inningEnd)
                            <div class="diagonal-line"></div>
                            @endif
                            @if($diamondUp)
                            <div class="diamond-up-left"></div>
                            <div class="diamond-up-right"></div>
                            @endif
                            @if($diamondDown)
                            <div class="diamond-down-left"></div>
                            <div class="diamond-down-right"></div>
                            @endif
                            <table class="play-quadrant-table">
                                <tr>
                                    @if ($bigK)
                                    <td class="play-quadrant play-blue abel-regular" rowspan="2" style="font-size:24pt;line-height:1">K</td>
                                    @else
                                    <x-score-quadrant :play="$play->results[2] ?? null" />
                                    @endif
                                    <x-score-quadrant :play="$play->results[1] ?? null" />
                                </tr>
                                <tr>
                                    @unless($bigK)<x-score-quadrant :play="$play->results[3] ?? null" />@endunless
                                    <x-score-quadrant :play="$bigK ? [$lastPitch, 'blue'] : $play->results[0] ?? null" />
                                </tr>
                                <tr style="height: 12.47px;">
                                    <td colspan="2">
                                        <div style="display:flex; justify-content:space-between;">
                                            <div class="pitch-sequence">{!! $pitches !!}</div>
                                            <div class="pitch-total">{{ $play->pitch_total ?? '' }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <div @class([
                                'run-circle',
                                'earned' => $runEarned === true,
                                'unearned' => $runEarned === false,
                            ])>{{ $outNumber ?? '' }}</div>
                        </div>
                    </td>
                    @endfor
                    @endforeach
                    @endif

                    <!-- Hitting Stats -->
                    <td style="text-align: center;">{{ $stats->PA ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->AB ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->R ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->H ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->stat('2') ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->stat('3') ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->HR ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->RBI ?: '' }}</td>
                    <td style="text-align: center;" class="non-ab-stat">{{ $stats->SAB ?: '' }}</td>
                    <td style="text-align: center;" class="non-ab-stat">{{ $stats->SAF ?: '' }}</td>
                    <td style="text-align: center;" class="non-ab-stat">{{ $stats->BBs ?: '' }}</td>
                    <td style="text-align: center;" class="non-ab-stat">{{ $stats->HPB ?: '' }}</td>
                    <td style="text-align: center;" class="non-ab-stat">{{ $stats->CI ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->SO ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->GDP ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->SB ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->CS ?: '' }}</td>
                    <td style="text-align: center;">{{ $stats->LOB ?: '' }}</td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
            <tbody>
                <tr>
                    <td colspan="41" style="height:5px;"></td>
                </tr>
            </tbody>
            <tbody class="pitcher-innings-section">
                <tr>
                    <!-- Put a table for pitchers fielding statistics when the DH is in use. -->
                    @if (isset($groupedBatters['P']))
                        <x-pitcher-fielding-stats-column :$groupedBatters class="fielding-stats" :stat="'DO'" />
                        <x-pitcher-fielding-stats-column :$groupedBatters class="spacing-col" />
                        <x-pitcher-fielding-stats-column :$groupedBatters class="fielding-stats" :stat="'PO'" />
                        <x-pitcher-fielding-stats-column :$groupedBatters class="fielding-stats" :stat="'A'" />
                        <x-pitcher-fielding-stats-column :$groupedBatters class="fielding-stats" :stat="'E'" />
                        <x-pitcher-fielding-stats-column :$groupedBatters class="spacing-col" />
                        <x-pitcher-fielding-stats-column :$groupedBatters class="fielding-stats" :position="true" />
                        <x-pitcher-fielding-stats-column :$groupedBatters class="fielding-stats" :changes="true" />
                        <x-pitcher-fielding-stats-column :$groupedBatters class="pitcher-name player-name" :detail="'name'" />
                        <x-pitcher-fielding-stats-column :$groupedBatters class="batting-jersey" :detail="'number'" />
                    @else
                        <td colspan="10" rowspan="8" style="border-spacing: 0; padding: 0;">&nbsp;</td>
                    @endif
                    <td class="main-stats-header">RUNS</td>
                    @foreach ($innings as $inning)
                    @for($i = 0; $i < $inning['width']; $i++)
                    <td class="totals-stat">
                        @unless($i !== $inning['width'] - 1 || is_null($inning['runs_total']))
                            {{ $inning['runs'] }} / {{ $inning['runs_total'] }}
                        @endunless
                    </td>
                    @endfor
                    @endforeach
                    <td class="totals-stat">{{  $teamStats->PA ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->AB ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->R ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->H ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->stat('2') ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->stat('3') ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->HR ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->RBI ?: '' }}</td>
                    <td class="totals-stat non-ab-stat">{{  $teamStats->SAB ?: '' }}</td>
                    <td class="totals-stat non-ab-stat">{{  $teamStats->SAF ?: '' }}</td>
                    <td class="totals-stat non-ab-stat">{{  $teamStats->BBs ?: '' }}</td>
                    <td class="totals-stat non-ab-stat">{{  $teamStats->HPB ?: '' }}</td>
                    <td class="totals-stat non-ab-stat">{{  $teamStats->CI ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->SO ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->GDP ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->SB ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->CS ?: '' }}</td>
                    <td class="totals-stat">{{  $teamStats->LOB ?: '' }}</td>
                </tr>
                <tr><td colspan="{{ array_sum(array_column($innings, 'width')) + 1 }}">&nbsp;</td></tr>
                <tr>
                    <td class="main-stats-header">Balls</td>
                    @foreach ($innings as $inning)
                    <x-score-pitcher-stat :inning="$inning" stat="b" />
                    @endforeach
                </tr>
                <tr>
                    <td class="main-stats-header">Strikes</td>
                    @foreach ($innings as $inning)
                    <x-score-pitcher-stat :inning="$inning" stat="s" />
                    @endforeach
                </tr>
                <tr>
                    <td class="main-stats-header">Pit</td>
                    @foreach ($innings as $inning)
                    <x-score-pitcher-stat :inning="$inning" stat="p" />
                    @endforeach
                </tr>
                <tr>
                    <td class="main-stats-header">BFP</td>
                    @foreach ($innings as $inning)
                    <x-score-pitcher-stat :inning="$inning" stat="bfp" />
                    @endforeach
                </tr>
                <tr>
                    <td class="main-stats-header">HITS</td>
                    @foreach ($innings as $inning)
                    <x-score-pitcher-stat :inning="$inning" stat="h" />
                    @endforeach
                </tr>
                <tr>
                    <td class="main-stats-header">LOB</td>
                    @foreach ($innings as $inning)
                    @for($i = 0; $i < $inning['width']; $i++)
                    <td>@if($i === $inning['width'] - 1){{ $inning['lob'] }}@endif</td>
                    @endfor
                    @endforeach
                </tr>
            </tbody>
        </table>

        <!-- Bottom Section -->
        <div class="bottom-section">
            <table>
                <tr>
                    <!-- Left Column: Pitchers and Catchers -->
                    <td style="width: 60%; vertical-align: top;">
                        <div class="section-title">PITCHERS</div>
                        <table class="summary">
                            <tr>
                                <td><strong>Name</strong></td>
                                <td><strong>IP</strong></td>
                                <td><strong>H</strong></td>
                                <td><strong>K</strong></td>
                                <td><strong>BB</strong></td>
                                <td><strong>HBP</strong></td>
                                <td><strong>RS</strong></td>
                                <td><strong>ER</strong></td>
                                <td><strong>WP</strong></td>
                                <td><strong>BLK</strong></td>
                                <td><strong>PO</strong></td>
                                <td><strong>PCS</strong></td>
                                <td><strong>BFP</strong></td>
                                <td><strong>B</strong></td>
                                <td><strong>S</strong></td>
                                <td><strong>PIT</strong></td>
                                <td><strong>W/L/S</strong></td>
                            </tr>
                            @forelse($pitchers as $pitcher)
                            @php
                            $stats = new \App\Helpers\StatsHelper($pitcher->stats);
                            $stats->derive();
                            @endphp
                            <tr>
                                <td class="player-name">{{ $pitcher->person->lastName }}, {{ $pitcher->person->firstName }}</td>
                                <td class="pitcher-stat">{{ \App\Helpers\StatsHelper::innings_format(isset($pitcher->stats['TO']) ? number_format(($pitcher->stats['TO'] ?? 0) / 3, 1) : '0.0') }}</td>
                                <td class="pitcher-stat">{{ $stats->HA ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->K ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->BB ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->HBP ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->RA ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->ER ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->WP ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->BLK ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->POs ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->PCS ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->BFP ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->Balls ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->Strikes ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->Pitches ?: '' }}</td>
                                <td class="pitcher-stat">{{ $stats->Win ? 'W' : ($stats->Loss ? 'L' : ($stats->Save ? 'S' : '')) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="17" style="text-align: center; color: #999;">No pitcher data available</td>
                            </tr>
                            @endforelse
                        </table>

                        <!-- Catchers Section -->
                        <div style="margin-top: 10px;">
                            <div class="section-title">CATCHERS</div>
                            <table class="summary">
                                <tr>
                                    <td style="width: 40%;"><strong>Name</strong></td>
                                    <td><strong>INN</strong></td>
                                    <td><strong>PB</strong></td>
                                    <td><strong>SB</strong></td>
                                    <td><strong>CS</strong></td>
                                </tr>
                                @php
                                $catchers = collect($battingOrder)->filter(function($batter) {
                                    return !empty(array_filter($batter['positions'], fn($pos) => $pos[2] == '2'));
                                });
                                @endphp
                                @forelse($catchers as $catcher)
                                @php
                                $stats = (new \App\Helpers\StatsHelper($catcher['player']->stats ?? []))->derive();
                                $do2 = $stats->stat('DO.2');
                                @endphp
                                <tr>
                                    <td class="player-name">{{ $catcher['name'] }}</td>
                                    <td class="catcher-stat">{{ $do2 ? \App\Helpers\StatsHelper::innings_format($do2 / 3) : '0' }}</td>
                                    <td class="catcher-stat">{{ $stats->PB }}</td>
                                    <td class="catcher-stat">{{ $stats->CSB }}</td>
                                    <td class="catcher-stat">{{ $stats->CCS }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">&nbsp;</td>
                                </tr>
                                @endforelse
                            </table>
                        </div>
                    </td>

                    <!-- Right Column: Score and Scorer -->
                    <td style="width: 40%; vertical-align: top;">
                        <div class="section-title">SCORE: {{ $opponent->name }} {{ $game->score[$isHome ? 0 : 1] ?? 0 }} - {{ $team->name }} {{ $game->score[$isHome ? 1 : 0] ?? 0 }}</div>

                        <!-- Pitchers of Record -->
                        @if($game->ended)
                        <div style="margin-top: 10px;">
                            <div class="section-title">PITCHERS OF RECORD</div>
                            <table class="summary">
                                <tr>
                                    <td style="width: 50%;"><strong>WIN:</strong></td>
                                    <td class="player-name">{{ $pitchersOfRecord['winning'] ? $pitchersOfRecord['winning']->person->fullName() : '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>LOSS:</strong></td>
                                    <td class="player-name">{{ $pitchersOfRecord['losing'] ? $pitchersOfRecord['losing']->person->fullName() : '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>SAVE:</strong></td>
                                    <td class="player-name">{{ $pitchersOfRecord['saving'] ? $pitchersOfRecord['saving']->person->fullName() : '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        @endif

                        <!-- Scorer Section -->
                        <div style="margin-top: 10px;">
                            <div class="section-title">SCORER</div>
                            <table class="summary">
                                <tr>
                                    <td class="player-name">{{ $game->scorer ? $game->scorer->name : '' }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
