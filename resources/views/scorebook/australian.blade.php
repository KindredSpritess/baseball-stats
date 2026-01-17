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
            display: flex;
            justify-content: space-between;
            padding: 8px;
            border-bottom: 2px solid #000;
            font-size: 12pt;
            font-weight: bold;
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
            display: table;
            width: 100%;
        }
        
        .grid-row {
            display: table-row;
        }
        
        .grid-cell {
            display: table-cell;
            border: 1px solid #000;
            vertical-align: top;
        }
        
        /* Fielding Section */
        .fielding-section {
            width: 80px;
            border-right: 2px solid #000;
        }
        
        .fielding-header {
            padding: 4px;
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
            background-color: #f0f0f0;
        }
        
        .fielding-row {
            display: flex;
            height: 35px;
            border-bottom: 1px solid #000;
        }
        
        .fielding-pos {
            width: 20px;
            border-right: 1px solid #000;
            text-align: center;
            padding: 2px;
            font-size: 7pt;
        }
        
        .fielding-player {
            flex: 1;
            padding: 2px;
            font-size: 7pt;
        }
        
        /* Batting Order Section */
        .batting-section {
            width: 120px;
            border-right: 2px solid #000;
        }
        
        .batting-header {
            padding: 4px;
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
            background-color: #f0f0f0;
        }
        
        .batting-row {
            display: flex;
            height: 35px;
            border-bottom: 1px solid #000;
        }
        
        .batting-number {
            width: 15px;
            border-right: 1px solid #000;
            text-align: center;
            padding: 2px;
            font-size: 7pt;
        }
        
        .batting-jersey {
            width: 20px;
            border-right: 1px solid #000;
            text-align: center;
            padding: 2px;
            font-size: 7pt;
        }
        
        .batting-name {
            flex: 1;
            padding: 2px 4px;
            font-size: 7pt;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Inning Columns */
        .innings-section {
            flex: 1;
            display: flex;
            border-right: 2px solid #000;
        }
        
        .inning-column {
            flex: 1;
            border-right: 1px solid #000;
        }
        
        .inning-column:last-child {
            border-right: none;
        }
        
        .inning-header {
            text-align: center;
            padding: 2px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            background-color: #f0f0f0;
            font-size: 7pt;
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
            height: 35px;
            border-bottom: 1px solid #000;
            display: flex;
            font-size: 6pt;
        }
        
        .stat-cell {
            flex: 1;
            border-right: 1px solid #000;
            text-align: center;
            padding: 2px;
        }
        
        .stat-cell:last-child {
            border-right: none;
        }
        
        /* Bottom sections */
        .bottom-section {
            display: flex;
            border-top: 2px solid #000;
        }
        
        .pitchers-section {
            flex: 1;
            border-right: 2px solid #000;
            padding: 5px;
        }
        
        .totals-section {
            width: 300px;
            padding: 5px;
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
            <div class="{{ !$isHome ? 'team-home' : '' }}">
                {{ $opponent->name }} ({{ !$isHome ? 'HOME' : 'AWAY' }})
            </div>
            <div>V</div>
            <div class="{{ $isHome ? 'team-home' : '' }}">
                {{ $team->name }} ({{ $isHome ? 'HOME' : 'AWAY' }})
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
        <div class="main-grid">
            <!-- Header Row -->
            <div class="grid-row">
                <!-- Fielding Header -->
                <div class="grid-cell fielding-section">
                    <div class="fielding-header">FIELDING</div>
                </div>
                
                <!-- Batting Order Header -->
                <div class="grid-cell batting-section">
                    <div class="batting-header">BATTING ORDER</div>
                </div>
                
                <!-- Innings Headers -->
                <div class="grid-cell innings-section">
                    <div style="display: flex;">
                        @foreach($innings as $inning)
                        <div class="inning-column">
                            <div class="inning-header">{{ $inning['number'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Stats Header -->
                <div class="grid-cell stats-section">
                    <div class="stats-header">BATTING</div>
                </div>
            </div>
            
            <!-- Runs Row -->
            <div class="grid-row">
                <div class="grid-cell fielding-section">
                    <div style="padding: 4px; text-align: center; font-weight: bold; height: 15px;">POS</div>
                </div>
                <div class="grid-cell batting-section">
                    <div style="padding: 4px; text-align: center; font-weight: bold; height: 15px;">TEAM: {{ $team->short_name }}</div>
                </div>
                <div class="grid-cell innings-section">
                    <div style="display: flex;">
                        @foreach($innings as $inning)
                        <div class="inning-column">
                            <div class="inning-runs">{{ $inning['runs'] }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="grid-cell stats-section">
                    <div style="padding: 4px; text-align: center; font-size: 6pt; height: 15px; display: flex;">
                        <div style="flex: 1;">PA</div>
                        <div style="flex: 1;">AB</div>
                        <div style="flex: 1;">R</div>
                        <div style="flex: 1;">H</div>
                        <div style="flex: 1;">RBI</div>
                        <div style="flex: 1;">BB</div>
                        <div style="flex: 1;">SO</div>
                    </div>
                </div>
            </div>
            
            <!-- Batter Rows -->
            @foreach($battingOrder as $index => $batter)
            <div class="grid-row">
                <!-- Fielding Position -->
                <div class="grid-cell fielding-section">
                    <div class="fielding-row">
                        <div class="fielding-pos">{{ $batter['spot'] }}</div>
                        <div class="fielding-player">{{ $batter['position'] }}</div>
                    </div>
                </div>
                
                <!-- Batter Info -->
                <div class="grid-cell batting-section">
                    <div class="batting-row">
                        <div class="batting-number">{{ $batter['spot'] }}</div>
                        <div class="batting-jersey">{{ $batter['number'] }}</div>
                        <div class="batting-name">{{ $batter['name'] }}</div>
                    </div>
                </div>
                
                <!-- Inning Cells for this Batter -->
                <div class="grid-cell innings-section">
                    <div style="display: flex;">
                        @foreach($innings as $inning)
                        <div class="inning-column">
                            <div class="inning-cell">
                                <!-- Diamond for tracking plays -->
                                <div class="diamond">
                                    <div class="diamond-inner">
                                        <!-- TODO: Add play notation here -->
                                        {{-- This would show result codes, base running, etc. --}}
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Stats for this Batter -->
                <div class="grid-cell stats-section">
                    <div class="stats-row">
                        <div class="stat-cell">-</div>
                        <div class="stat-cell">-</div>
                        <div class="stat-cell">-</div>
                        <div class="stat-cell">-</div>
                        <div class="stat-cell">-</div>
                        <div class="stat-cell">-</div>
                        <div class="stat-cell">-</div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Bottom Section -->
        <div class="bottom-section">
            <!-- Pitchers Section -->
            <div class="pitchers-section">
                <div class="section-title">PITCHERS</div>
                <table class="summary">
                    <tr>
                        <td><strong>Name</strong></td>
                        <td><strong>IP</strong></td>
                        <td><strong>H</strong></td>
                        <td><strong>R</strong></td>
                        <td><strong>ER</strong></td>
                        <td><strong>BB</strong></td>
                        <td><strong>K</strong></td>
                    </tr>
                    <!-- TODO: Add pitcher data -->
                    <tr>
                        <td colspan="7" style="text-align: center; color: #999;">Pitcher data to be populated</td>
                    </tr>
                </table>
                
                <div style="margin-top: 10px;">
                    <table class="summary">
                        <tr>
                            <td><strong>Winning Pitcher:</strong></td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><strong>Losing Pitcher:</strong></td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><strong>Save:</strong></td>
                            <td>-</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Totals Section -->
            <div class="totals-section">
                <div class="section-title">SCORE</div>
                <table class="summary">
                    <tr>
                        <td><strong>{{ $opponent->short_name }}</strong></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td><strong>{{ $team->short_name }}</strong></td>
                        <td>-</td>
                    </tr>
                </table>
                
                <div style="margin-top: 10px;">
                    <div class="section-title">SCORER</div>
                    <table class="summary">
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td>&nbsp;</td>
                        </tr>
                    </table>
                </div>
            </div>
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
