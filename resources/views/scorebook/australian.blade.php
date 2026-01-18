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
            background: white;
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
            width: 20px;
            height: 20px;
            margin: 2px auto 0;
            border-collapse: collapse;
            position: relative;
        }
        
        .play-quadrant {
            width: 10px;
            height: 10px;
            border: 0.5px solid #000;
            font-size: 5pt;
            text-align: center;
            vertical-align: middle;
        }
        
        .run-circle {
            width: 8px;
            height: 8px;
            border: 1px solid #000;
            border-radius: 50%;
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
        }
        
        .run-circle.earned {
            background: #000;
        }
        
        .pitch-sequence {
            font-size: 5pt;
            text-align: center;
            padding: 1px;
            margin-top: 1px;
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
            <!-- Totals/Summary Row -->
            <tbody>
                <tr style="background-color: #f0f0f0;">
                    <td colspan="8" style="text-align: right; padding: 2px 4px; font-weight: bold;">RUNS</td>
                    <td colspan="2">&nbsp;</td>
                    <td>&nbsp;</td>
                    @foreach($innings as $inning)
                    <td style="text-align: center; font-weight: bold;">{{ $inning['runs'] }}</td>
                    @endforeach
                    <td colspan="18" style="text-align: center; font-weight: bold;">
                        TOTAL: {{ array_sum(array_column($innings, 'runs')) }}
                    </td>
                </tr>
                <tr style="background-color: #f0f0f0;">
                    <td colspan="8" style="text-align: right; padding: 2px 4px; font-weight: bold;">LOB</td>
                    <td colspan="2">&nbsp;</td>
                    <td>&nbsp;</td>
                    @foreach($innings as $inning)
                    <td style="text-align: center;">{{ $inning['lob'] ?? 0 }}</td>
                    @endforeach
                    <td colspan="18">&nbsp;</td>
                </tr>
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
                    <td style="text-align: center;">{{ $stats->DO }}</td>
                    <td>&nbsp;</td>
                    <td style="text-align: center;">{{ $stats->PO }}</td>
                    <td style="text-align: center;">{{ $stats->A }}</td>
                    <td style="text-align: center;">{{ $stats->E }}</td>
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
                        <!-- Play cell with 4 quadrants and circle -->
                        <div class="play-cell">
                            <table class="play-quadrant-table">
                                <tr>
                                    <td class="play-quadrant"></td>
                                    <td class="play-quadrant"></td>
                                </tr>
                                <tr>
                                    <td class="play-quadrant"></td>
                                    <td class="play-quadrant"></td>
                                </tr>
                            </table>
                            <div class="run-circle"></div>
                            <div class="pitch-sequence">
                                <!-- Pitch sequence and count go here -->
                            </div>
                        </div>
                    </td>
                    @endforeach

                    <!-- Hitting Stats -->
                    <td style="text-align: center;">{{ $stats->PA }}</td>
                    <td style="text-align: center;">{{ $stats->AB }}</td>
                    <td style="text-align: center;">{{ $stats->R }}</td>
                    <td style="text-align: center;">{{ $stats->H }}</td>
                    <td style="text-align: center;">{{ $stats->stat('2') }}</td>
                    <td style="text-align: center;">{{ $stats->stat('3') }}</td>
                    <td style="text-align: center;">{{ $stats->HR }}</td>
                    <td style="text-align: center;">{{ $stats->RBI }}</td>
                    <td style="text-align: center;">{{ $stats->SAB }}</td>
                    <td style="text-align: center;">{{ $stats->SAF }}</td>
                    <td style="text-align: center;">{{ $stats->BBs }}</td>
                    <td style="text-align: center;">{{ $stats->HPB }}</td>
                    <td style="text-align: center;">{{ $stats->CI }}</td>
                    <td style="text-align: center;">{{ $stats->SO }}</td>
                    <td style="text-align: center;">{{ $stats->GDP }}</td>
                    <td style="text-align: center;">{{ $stats->SB }}</td>
                    <td style="text-align: center;">{{ $stats->CS }}</td>
                    <td style="text-align: center;">{{ $stats->LOB }}</td>
                </tr>
                @endforeach
                @endforeach
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
                                <td>{{ $pitcher->person->fullName() }}</td>
                                <td style="text-align: center;">{{ \App\Helpers\StatsHelper::innings_format(isset($pitcher->stats['TO']) ? number_format(($pitcher->stats['TO'] ?? 0) / 3, 1) : '0.0') }}</td>
                                <td style="text-align: center;">{{ $stats->HA }}</td>
                                <td style="text-align: center;">{{ $stats->K }}</td>
                                <td style="text-align: center;">{{ $stats->BB }}</td>
                                <td style="text-align: center;">{{ $stats->HBP }}</td>
                                <td style="text-align: center;">{{ $stats->RA }}</td>
                                <td style="text-align: center;">{{ $stats->ER }}</td>
                                <td style="text-align: center;">{{ $stats->WP }}</td>
                                <td style="text-align: center;">{{ $stats->BLK }}</td>
                                <td style="text-align: center;">{{ $stats->PO }}</td>
                                <td style="text-align: center;">{{ $stats->PCS }}</td>
                                <td style="text-align: center;">{{ $stats->BFP }}</td>
                                <td style="text-align: center;">{{ $stats->Balls }}</td>
                                <td style="text-align: center;">{{ $stats->Strikes }}</td>
                                <td style="text-align: center;">{{ $stats->Pitches }}</td>
                                <td style="text-align: center;">{{ $stats->Win ? 'W' : ($stats->Loss ? 'L' : ($stats->Save ? 'S' : '')) }}</td>
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
                                    <td><strong>SCS</strong></td>
                                </tr>
                                @php
                                $catchers = collect($battingOrder)->filter(function($batter) {
                                    return $batter['position'] == '2';
                                });
                                @endphp
                                @forelse($catchers as $catcher)
                                @php
                                $stats = new \App\Helpers\StatsHelper($catcher['stats']);
                                $stats->derive();
                                $do2 = $stats->stat('DO.2');
                                @endphp
                                <tr>
                                    <td>{{ $catcher['name'] }}</td>
                                    <td style="text-align: center;">{{ $do2 ? number_format($do2 / 3, 1) : '0.0' }}</td>
                                    <td style="text-align: center;">{{ $stats->PB }}</td>
                                    <td style="text-align: center;">{{ $stats->CSB }}</td>
                                    <td style="text-align: center;">{{ $stats->CCS }}</td>
                                    <td style="text-align: center;">-</td>
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
                        <div class="section-title">SCORE</div>
                        <table class="summary">
                            <tr>
                                <td style="width: 50%;"><strong>{{ $opponent->short_name }}</strong></td>
                                <td style="text-align: center;">{{ $game->score[$isHome ? 0 : 1] ?? 0 }}</td>
                            </tr>
                            <tr>
                                <td><strong>{{ $team->short_name }}</strong></td>
                                <td style="text-align: center;">{{ $game->score[$isHome ? 1 : 0] ?? 0 }}</td>
                            </tr>
                        </table>
                        
                        <!-- Pitchers of Record -->
                        <div style="margin-top: 10px;">
                            <table class="summary">
                                <tr>
                                    <td style="width: 50%;"><strong>PITCHER WIN:</strong></td>
                                    <td>{{ $pitchersOfRecord['winning'] ? $pitchersOfRecord['winning']->person->fullName() : '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>LOSS:</strong></td>
                                    <td>{{ $pitchersOfRecord['losing'] ? $pitchersOfRecord['losing']->person->fullName() : '-' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>SAVE:</strong></td>
                                    <td>{{ $pitchersOfRecord['saving'] ? $pitchersOfRecord['saving']->person->fullName() : '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <!-- Scorer Section -->
                        <div style="margin-top: 10px;">
                            <div class="section-title">SCORER</div>
                            <table class="summary">
                                <tr>
                                    <td>{{ $game->scorer ? $game->scorer->name : '' }}</td>
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
