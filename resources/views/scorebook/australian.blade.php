<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Australian Scorebook - Game {{ $game->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            padding: 10px;
        }
        
        .scorebook {
            width: 100%;
            border: 2px solid #000;
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
        
        .header-cell:last-child {
            border-right: none;
        }
        
        .team-names {
            width: 100%;
            border-bottom: 2px solid #000;
            font-size: 12pt;
            font-weight: bold;
        }
        
        .team-names table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .team-names td {
            padding: 8px;
            text-align: center;
        }
        
        .team-names td:first-child {
            text-align: left;
        }
        
        .team-names td:last-child {
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
        }
        
        .game-notes-title {
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding: 2px;
        }
        
        /* Main Grid */
        .main-grid {
            width: 100%;
            border-collapse: collapse;
        }
        
        .main-grid th, .main-grid td {
            border: 1px solid #000;
            vertical-align: top;
        }
        
        /* Fielding Section */
        .fielding-section {
            width: 80px;
            border-right: 2px solid #000 !important;
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
            border-right: 2px solid #000 !important;
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
        
        .batting-name {
            text-align: left;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
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
        }
        
        .inning-runs {
            text-align: center;
            padding: 2px;
            border-bottom: 1px solid #000;
            font-size: 7pt;
            height: 15px;
        }
        
        .inning-cell {
            height: 35px;
            border-bottom: 1px solid #000;
            position: relative;
            padding: 2px;
        }
        
        /* Diamond for tracking plays */
        .diamond {
            width: 20px;
            height: 20px;
            margin: auto;
            position: relative;
            transform: rotate(45deg);
            border: 1px solid #000;
        }
        
        .diamond-inner {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 6pt;
            transform: translate(-50%, -50%) rotate(-45deg);
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
        }
        
        .stats-row td:last-child {
            border-right: none;
        }
        
        /* Bottom sections */
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
        }
        
        table.summary td {
            border: 1px solid #000;
            padding: 2px 4px;
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
    </style>
</head>
<body>
    <div class="scorebook">
        <!-- Team Names -->
        <div class="team-names">
            <table>
                <tr>
                    <td class="{{ !$isHome ? 'team-home' : '' }}">
                        {{ $opponent->name }} ({{ !$isHome ? 'HOME' : 'AWAY' }})
                    </td>
                    <td>V</td>
                    <td class="{{ $isHome ? 'team-home' : '' }}">
                        {{ $team->name }} ({{ $isHome ? 'HOME' : 'AWAY' }})
                    </td>
                </tr>
            </table>
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
                    <th rowspan="3" colspan="8" class="fielding-section">
                        <div class="fielding-header">FIELDING</div>
                    </th>
                    <th rowspan="1" colspan="2" class="batting-section">
                        <div class="batting-header">BATTING ORDER</div>
                    </th>
                    <th>&nbsp;</th>
                    @foreach($innings as $inning)
                    <th class="inning-column"><div class="inning-header">{{ $inning['number'] }}</div></th>
                    @endforeach
                    <th rowspan="3" colspan="20" class="stats-section">
                        <div class="stats-header">BATTING</div>
                    </th>
                </tr>
                <tr>
                    <!-- Batting Order Sub-Header -->
                    <th rowspan="3" colspan="2" class="batting-section">
                        <div style="padding: 4px; text-align: center; font-weight: bold; height: 15px;">TEAM: {{ $team->name }}</div>
                    </th>
                    <!-- Assist row for each inning -->
                    <th>A</th>
                    @foreach($innings as $inning)
                    <td class="inning-fielding">
                        <!-- loop over plays within the inning,
                            * if it has a assist write the number,
                            * if it has a putout or error leave a space
                            * other wise leave blank
                        -->
                        &nbsp;
                    </td>
                    @endforeach
                </tr>
                <tr>
                    <!-- Putout row for each inning -->
                    <th>PO</th>
                    @foreach($innings as $inning)
                    <td class="inning-fielding">
                        <!-- loop over plays within the inning,
                            * if it has a putout write the number,
                            * if it has a assist or error leave a space
                            * other wise leave blank
                        -->
                        &nbsp;
                    </td>
                    @endforeach
                </tr>
                <tr>
                    <th>DO</th>
                    <th>&nbsp;</th>
                    <th>PO</th>
                    <th>A</th>
                    <th>E</th>
                    <th>&nbsp;</th>
                    <th>Pos</th>
                    <th>Ch</th>
                    <!-- Error row for each inning -->
                    <th>E</th>
                    @foreach($innings as $inning)
                    <td class="inning-fielding">
                        <!-- loop over plays within the inning,
                            * if it has a error write the number,
                            * if it has a assist or putout leave a space
                            * other wise leave blank
                        -->
                        &nbsp;
                    </td>
                    @endforeach

                    <!-- Hitting Stats Headers -->
                    <th>PA</th>
                    <th>AB</th>
                    <th>R</th>
                    <th>H</th>
                    <th>2</th>
                    <th>3</th>
                    <th>HR</th>
                    <th>RBI</th>
                    <th>SAB</th>
                    <th>SAF</th>
                    <th>BB</th>
                    <th>HBP</th>
                    <th>CI</th>
                    <th>K</th>
                    <th>GDP</th>
                    <th>SB</th>
                    <th>CS</th>
                    <th>LOB</th>
                </tr>
            </thead>
            <!-- Runs Row -->
            <tbody>
                <!-- Batter Rows -->
                @php
                $groupedBatters = collect($battingOrder)->groupBy('spot');
                @endphp
                @foreach($groupedBatters as $spot => $batters)
                @php $rowspan = count($batters); @endphp
                @foreach($batters as $index => $batter)
                @php
                $stats = new \App\Helpers\StatsHelper($batter['stats']);
                $stats->derive();
                @endphp
                <tr>
                    <td>{{ $stats->DO }}</td>
                    <td>&nbsp;</td>
                    <td>{{ $stats->PO }}</td>
                    <td>{{ $stats->A }}</td>
                    <td>{{ $stats->E }}</td>
                    <td>&nbsp;</td>
                    <td class="fielding-player">{{ $batter['position'] ?: 'EH' }}</td>
                    <td class="fielding-pos">{{ $batter['spot'] }}</td>

                    <!-- Batter name and jersey number -->
                    <td class="batting-name">{{ $batter['name'] }}</td>
                    <td class="batting-jersey">{{ $batter['number'] }}</td>
                    @if ($loop->first)
                    <td class="batting-number" rowspan="{{ $rowspan }}">{{ $spot }}</td>
                    @endif

                    <!-- Plate appearance cells. -->
                    @foreach($innings as $inning)
                    <td class="inning-cell">
                        <!-- Diamond for tracking plays -->
                        <div class="diamond">
                            <div class="diamond-inner">
                                <!-- TODO: Add play notation here -->
                                {{-- This would show result codes, base running, etc. --}}
                            </div>
                        </div>
                    </td>
                    @endforeach

                    <!-- Hitting Stats -->
                    <td>{{ $stats->PA }}</td>
                    <td>{{ $stats->AB }}</td>
                    <td>{{ $stats->R }}</td>
                    <td>{{ $stats->H }}</td>
                    <td>{{ $stats->stat('2') }}</td>
                    <td>{{ $stats->stat('3') }}</td>
                    <td>{{ $stats->HR }}</td>
                    <td>{{ $stats->RBI }}</td>
                    <td>{{ $stats->SAB }}</td>
                    <td>{{ $stats->SAF }}</td>
                    <td>{{ $stats->BBs }}</td>
                    <td>{{ $stats->HPB }}</td>
                    <td>{{ $stats->CI }}</td>
                    <td>{{ $stats->SO }}</td>
                    <td>{{ $stats->GDP }}</td>
                    <td>{{ $stats->SB }}</td>
                    <td>{{ $stats->CS }}</td>
                    <td>{{ $stats->LOB }}</td>
                </tr>
                @endforeach
                @endforeach
            </tbody>
        </table>
        
        <!-- Bottom Section -->
        <div class="bottom-section">
            <table>
                <tr>
                    <td class="pitchers-section">
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
                                <td>{{ $pitcher->person->fullName() }}</td>
                                <td>{{ \App\Helpers\StatsHelper::innings_format(isset($pitcher->stats['TO']) ? number_format(($pitcher->stats['TO'] ?? 0) / 3, 1) : '0.0') }}</td>
                                <td>{{ $stats->HA }}</td>
                                <td>{{ $stats->K }}</td>
                                <td>{{ $stats->BB }}</td>
                                <td>{{ $stats->HBP }}</td>
                                <td>{{ $stats->RA }}</td>
                                <td>{{ $stats->ER }}</td>
                                <td>{{ $stats->WP }}</td>
                                <td>{{ $stats->BLK }}</td>
                                <td>{{ $stats->PO }}</td>
                                <td>{{ $stats->PCS }}</td>
                                <td>{{ $stats->BFP }}</td>
                                <td>{{ $stats->Balls }}</td>
                                <td>{{ $stats->Strikes }}</td>
                                <td>{{ $stats->Pitches }}</td>
                                <td>{{ $stats->Win ? 'W' : ($stats->Loss ? 'L' : ($stats->Save ? 'S' : '')) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="17" style="text-align: center; color: #999;">No pitcher data available</td>
                            </tr>
                            @endforelse
                        </table>
                        
                        <div style="margin-top: 10px;">
                            <table class="summary">
                                <tr>
                                    <td><strong>Winning Pitcher:</strong></td>
                                    <td>{{ $pitchersOfRecord['winning'] ? $pitchersOfRecord['winning']->person->fullName() : '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Losing Pitcher:</strong></td>
                                    <td>{{ $pitchersOfRecord['losing'] ? $pitchersOfRecord['losing']->person->fullName() : '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Save:</strong></td>
                                    <td>{{ $pitchersOfRecord['saving'] ? $pitchersOfRecord['saving']->person->fullName() : '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    
                    <!-- Totals Section -->
                    <td class="totals-section">
                        <div class="section-title">SCORE</div>
                        <table class="summary">
                            <tr>
                                <td><strong>{{ $opponent->short_name }}</strong></td>
                                <td>{{ $game->score[$isHome ? 0 : 1] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ $team->short_name }}</strong></td>
                                <td>{{ $game->score[$isHome ? 1 : 0] ?? 0 }}</td>
                            </tr>
                        </table>
                        
                        <div style="margin-top: 10px;">
                            <div class="section-title">SCORER</div>
                            <table class="summary">
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td>{{ $game->scorer ? $game->scorer->name : '-' }}</td>
                            </tr>
                        </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Notation Notes -->
        <div class="notation-notes">
            <h4>⚠ Notation Implementation Notes:</h4>
            <ul>
                <li><strong>Diamond notation:</strong> The diamond in each cell should show the result of the at-bat using standard baseball scoring notation (K for strikeout, 6-3 for groundout, 1B/2B/3B/HR for hits, BB for walk, etc.)</li>
                <li><strong>Base paths:</strong> Lines should be drawn on the diamond to show base running (1st to 2nd, advancement to 3rd, scoring)</li>
                <li><strong>Run tracking:</strong> Numbers should be used instead of tally marks for counting runs, hits, errors, etc.</li>
                <li><strong>Pitcher changes:</strong> Should be noted in fielding column when they occur</li>
                <li><strong>Substitutions:</strong> New players entering the game should be shown in the lineup</li>
                <li><strong>Play details:</strong> Additional notation space around the diamond for fielding details, errors, stolen bases</li>
                <li><strong>Count information:</strong> Ball-strike count could be shown in smaller text near the diamond</li>
                <li><strong>Scoring symbols:</strong> Filled vs unfilled diamonds or other markers to indicate runs scored</li>
            </ul>
        </div>
    </div>
</body>
</html>
