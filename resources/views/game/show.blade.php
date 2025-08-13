<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="/jquery.min.js"></script>
    <link rel="stylesheet" href="/styles.css" />
    @if ($game->locked)
    <link rel="stylesheet" href="/game.css" />
    @else
    <link rel="stylesheet" href="/scorers.css" />
    <link rel="stylesheet" href="/css/player-lineup-add.css" />
    <script src="/js/player-lineup-add.js"></script>
    @endif
    <script src="https://kit.fontawesome.com/cc3e56010d.js" crossorigin="anonymous"></script>
    <title>{{ $game->away_team->short_name }} @ {{ $game->home_team->short_name }}</title>
</head>
<body>
@if ($game->locked)
<div class="mobile-menu">
    <a href="#play-by-play">Plays</a>
    <a href="#away">{{ $game->away_team->short_name }}</a>
    <a href="#home">{{ $game->home_team->short_name }}</a>
</div>
@endif
<table id='game-view'>
    <tr style="max-height: 100%;">
        <td class='mobile-hide' x-column='away'>
            <x-box-score :team="$game->away_team" :lineup="$game->lineup[0]" :atbat="$game->atBat[0]" :defending="!!($game->half)" />
        </td>
        <td style='text-align: center; width: 100%;' class='mobile-hide' x-column='play-by-play'>
            <h2>{{ $game->firstPitch }} at {{ $game->location }}</h2>
            @if ($game->locked)
            <x-line-score :game="$game" />
            @else
            <h3>{{ implode(' - ', $game->score) }}</h3>

            <!-- Button to show player lineup add component -->
            <div class="add-player-button-container">
                <button id="show-add-player" class="btn btn-primary">Add Player to Lineup</button>
            </div>

            <!-- Player Lineup Add Component (initially hidden, shown via hash) -->
            <div class="lineup-add-container" style="display: none;">
                <div class="team-selector">
                    <button class="team-btn" data-team="away">{{ $game->away_team->short_name }}</button>
                    <button class="team-btn active" data-team="home">{{ $game->home_team->short_name }}</button>
                </div>

                <div id="away-team-add" class="team-add-section" style="display: none;">
                    <x-player-lineup-add :game="$game" :team="$game->away_team" />
                </div>

                <div id="home-team-add" class="team-add-section">
                    <x-player-lineup-add :game="$game" :team="$game->home_team" />
                </div>
            </div>
            @endif
            <p>
                @if ($game->half)
                    ⬇️
                @else
                    ⬆️
                @endif
                {{ $game->inning }}
                ({{ $game->balls }} - {{ $game->strikes }}) {{ $game->outs }} outs
            </p>
            <svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg" viewBox="0 0 447.94775 448.12701" xml:space="preserve" fill="#00000000" stroke="#00000000">
                <g shape-rendering="auto" image-rendering="auto" color-rendering="auto" color-interpolation="sRGB">
                    <!-- Border -->
                    <path style="fill:#5a3392" d="m 224.02066,0 c -51.375,0 -104.961,18.027 -145.843997,50.219 -37.233,29.316 -61.724,68.332 -77.84399963,120.094 -0.785,2.639 -0.16,30.431 1.65600003,32.5 L 180.08366,380.845 c -2.605,5.889 -4.125,12.355 -4.125,19.188 0,26.418 21.648,48.094 48.063,48.094 26.415,0 47.969,-21.678 47.969,-48.094 0,-6.807 -1.486,-13.25 -4.063,-19.125 l 178.031,-178.031 c 1.816,-2.068 2.442,-29.922 1.656,-32.561 -15.25,-51.344 -40.622,-90.785 -77.844,-120.094 -40.883,-32.192 -94.374,-50.219 -145.75,-50.219 z">&#10;                </path>&#10;                <path style="fill:#fb9761" d="m 224.00866,159.639 121.063,121.187 -86.344,86.344 c -8.749,-9.23 -21.048,-15.064 -34.719,-15.064 -13.677,0 -26.001,5.83 -34.781,15.064 l -86.375,-86.375 z" />
                    <!-- Outfield -->
                    <path style="fill:#4bb33d" d="m 224.00866,16.075 c 47.618,0 98.056,16.903 135.844,46.655 33.519,26.396 63.19668,78.8981 77.79468,125.6151 l -80.29468,79.7909 -128.125,-125.154 c -1.632,-1.404 -3.761,-2.092 -5.906,-1.906 -1.706,0.125 -3.327,0.793 -4.625,1.906 L 91.518069,269.4604 7.1171623,185.0595 C 22.508162,137.8195 54.602663,89.084 88.070663,62.731 125.85766,32.977 176.39066,16.074 224.00866,16.075 Z" />
                    <!-- Infield -->
                    <path style="fill:#4bb33d" d="m 223.99966,181.055 99.295,99.771 -67.255,69.37521 c -7.176,-7.57 -20.828,-13.76821 -32.04,-13.76821 -11.217,0 -24.789,5.49011 -31.991,13.06111 l -67.381,-68.66811 z" />
                    <!-- Dirt -->
                    <path style="fill:#fb9761" d="m 224.009,246.014 c 8.931,0 15.969,7.131 15.969,16.063 0,8.932 -7.037,15.938 -15.969,15.938 -8.931,0 -16.062,-7.006 -16.062,-15.938 -0.001,-8.932 7.13,-16.063 16.062,-16.063 z" />
                    <path style="fill:#fb9761" d="m 224.009,368.043 c 17.768,0 32.031,14.232 32.031,32 0,17.768 -14.263,32.031 -32.031,32.031 -17.768,0 -32,-14.264 -32,-32.031 0,-17.767 14.232,-32 32,-32 z" />
                </g>

                @if ($game->fielding('1'))
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif" x=224 y=260 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('1')->person->lastName }}, {{ $game->fielding('1')->person->firstName[0] }}</text>
                @endif
                @if ($game->fielding('2'))
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=224 y=435 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('2')->person->lastName }}, {{ $game->fielding('2')->person->firstName[0] }}</text>
                @endif
                @if ($game->fielding('3'))
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=344 y=310 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('3')->person->lastName }}, {{ $game->fielding('3')->person->firstName[0] }}</text>
                @endif
                @if ($game->fielding('4'))
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=284 y=210 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('4')->person->lastName }}, {{ $game->fielding('4')->person->firstName[0] }}</text>
                @endif
                @if ($game->fielding('5'))
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=104 y=310 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('5')->person->lastName }}, {{ $game->fielding('5')->person->firstName[0] }}</text>
                @endif
                @if ($game->fielding('6'))
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=164 y=210 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('6')->person->lastName }}, {{ $game->fielding('6')->person->firstName[0] }}</text>
                @endif
                @if ($game->fielding('7'))
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=104 y=130 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('7')->person->lastName }}, {{ $game->fielding('7')->person->firstName[0] }}</text>
                @endif
                @if ($game->fielding('8'))
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=224 y=80 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('8')->person->lastName }}, {{ $game->fielding('8')->person->firstName[0] }}</text>
                @endif
                @if ($game->fielding('9'))
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=344 y=130 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('9')->person->lastName }}, {{ $game->fielding('9')->person->firstName[0] }}</text>
                @endif
                @if ($game->hitting())
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=224 y=405 font-weight="bold" fill="black" stroke="white" stroke-width="1px">{{ $game->hitting()->person->lastName }}, {{ $game->hitting()->person->firstName[0] }}</text>
                @endif
                @if ($game->bases[0])
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=344 y=270 font-weight="bold" fill="black" stroke="white" stroke-width="1px">{{ $game->bases['0']->person->lastName }}</text>
                @endif
                @if ($game->bases[1])
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=224 y=180 font-weight="bold" fill="black" stroke="white" stroke-width="1px">{{ $game->bases['1']->person->lastName }}</text>
                @endif
                @if ($game->bases[2])
                <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=104 y=280 font-weight="bold" fill="black" stroke="white" stroke-width="1px">{{ $game->bases['2']->person->lastName }}</text>
                @endif
                @foreach ($game->ballsInPlay as $ball)
                    <circle cx="{{ $ball->position[0] }}" cy="{{ $ball->position[1] }}" r="10" fill="{{ $ball->lastPlay ? "red" : "cyan" }}" />
                @endforeach
            </svg>
            @if (!$game->locked)
            <textarea id='plays' style="width:100%" rows="20">
@foreach ($game->plays as $play)
{{ $play->play }}
@endforeach
</textarea>
            <button id='submitPlays'>Update From Start</button>
            <form id='log' action="">
                <input type="hidden" name="play" id="gamelog" />
                <input type="hidden" name="inplay" id="inplay" />
                <table>
                    <tr>
                        <th>Pitches</th>
                        <th>Batter</th>
                        <th>First</th>
                        <th>Second</th>
                        <th>Third</th>
                    </tr>
                    <tr>
                        <td><input id='pitches' autofocus autocomplete='off' /></td>
                        <th><input id='batter' autocomplete='off' /></th>
                        <th><input id='first' autocomplete='off' @disabled(!$game->bases[0]) /></th>
                        <th><input id='second' autocomplete='off' @disabled(!$game->bases[1]) /></th>
                        <th><input id='third' autocomplete='off' @disabled(!$game->bases[2]) /></th>
                    </tr>
                </table>
                <input type="submit" />
            </form>
            @endif
            <!-- Put a clickable innings selector. -->
            @if ($game->locked)
                <div class="innings-selector">
                    @for ($i = 1; $i <= $game->inning; $i++)
                    <a href="#play-by-play" class="inning-link" data-inning="{{ $i }}">{{ $i }}</a>
                    @endfor
                </div>
            @endif
            <div id='play-by-play'>
                @foreach ($game->plays as $i => $play)
                    @if ($play->human)
                    <div @class([ 'run-scoring' => $play->run_scoring ]) data-play-id="{{ $i }}" data-inning="{{ $play->inning }}" data-inning-half="{{ $play->inning_half }}">{{ $play->human }}</div>
                    @endif
                    @if ($play->game_event)
                    <div class='game-event'>{{ $play->game_event }}</div>
                    @endif
                @endforeach
            </div>
        </td>
        <td class='mobile-hide' x-column='home'>
            <x-box-score :team="$game->home_team" :lineup="$game->lineup[1]" :atbat="$game->atBat[1]" :defending="!($game->half)" />
        </td>
    </tr>
</table>
@if (!$game->locked)
<script>
    function dsub(spot) {
        $('#pitches').val(`DC #${spot} -> `);
        $('#pitches').focus();
    }

    // When the plays textarea is in focused, get the cursor position and highlight the equivalent play in the play-by-play section
    $('#plays').on('click', function() {
        const cursorPos = this.selectionStart;
        const lines = this.value.split('\n');
        let currentLine = 0;
        let charCount = 0;

        for (let i = 0; i < lines.length; i++) {
            charCount += lines[i].length + 1; // +1 for the newline character
            if (charCount > cursorPos) {
                currentLine = i;
                break;
            }
        }

        $('#play-by-play div').removeClass('highlighted');
        $(`#play-by-play [data-play-id="${currentLine}"]`).addClass('highlighted');
        // Scroll to the highlighted play
        const highlightedPlay = $(`#play-by-play [data-play-id="${currentLine}"]`);
        if (highlightedPlay.length) {
            highlightedPlay[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            // $('#play-by-play').scrollTop(highlightedPlay.position().top + $('#play-by-play').scrollTop() - $('#play-by-play').height() / 2);
        }
    });

    $('#log').on('submit', (event) => {
        event.preventDefault();
        const parts = [];
        if ($('#inplay').val() && $('#batter').val()) {
            parts.unshift($('#inplay').val());
        }
        if (parts.length || $('#third').val()) {
            parts.unshift($('#third').val());
        }
        if (parts.length || $('#second').val()) {
            parts.unshift($('#second').val());
        }
        if (parts.length || $('#first').val()) {
            parts.unshift($('#first').val());
        }
        if (parts.length || $('#batter').val()) {
            parts.unshift($('#batter').val());
        }
        if (parts.length || $('#pitches').val()) {
            parts.unshift($('#pitches').val());
        }
        $.ajax("{{ route('gamelog', ['game' => $game->id]) }}", {
            accepts: {
                gamestate: 'application/json'
            },
            data: {
                'play': parts.join(','),
                '_token': '{{ csrf_token() }}',
            },
            method: 'PUT'
        }).then(() => {location.reload()});
    });

    $('#submitPlays').on('click', (e) => {
        e.target.disabled = true;
        $.ajax("{{ route('fullgamelog', ['game' => $game->id]) }}", {
            accepts: {
                gamestate: 'application/json'
            },
            data: {
                'plays': $('#plays').val(),
            },
            method: 'PATCH'
        }).then(() => {location.reload()});
    });

    $(document).ready(function(){
        $('textarea').each((i, e) => {
            $(e).scrollTop(e.scrollHeight);
        });
        $('#play-by-play').scrollTop($('#play-by-play')[0].scrollHeight);
        
        // Add Player button click handler
        $('#show-add-player').on('click', function() {
            // Get current hash
            const currentHash = window.location.hash;
            
            // Check if we already have team parameter
            let team = 'home'; // Default to home team
            if (currentHash.includes('team=away')) {
                team = 'away';
            }
            
            // Update hash to show add player component with team
            window.location.hash = `#add-player&team=${team}`;
        });
        
        // Hide add player button when component is shown
        $(window).on('hashchange', function() {
            if (window.location.hash.includes('add-player')) {
                $('.add-player-button-container').hide();
            } else {
                $('.add-player-button-container').show();
            }
        });
        
        // Initial check for hash
        if (window.location.hash.includes('add-player')) {
            $('.add-player-button-container').hide();
        }
    });

    let bb = null;
    $('#Layer_1').on('click', e => {
        const {offsetX, offsetY } = e.originalEvent;
        // Draw a circle centered on pos, scaled to viewBox.
        const cx = (offsetX / e.currentTarget.clientWidth * 447.94775).toFixed(2);
        const cy = (offsetY / e.currentTarget.clientHeight * 448.12701).toFixed(2);
        if (!bb) {
            bb = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            bb.setAttribute('r', '10');
            bb.setAttribute('fill', 'red');
            document.getElementById('Layer_1').appendChild(bb);
        }
        bb.setAttribute('cx', cx);
        bb.setAttribute('cy', cy);
        $('#inplay').val(`${cx}:${cy}`);
    });
</script>
@else
<script>
    $(document).ready(() => {
        const [, column] = (window.location.hash || '#play-by-play').split('#');
        $(`[x-column=${column}]`).show();
    });
    $('.mobile-menu a').on('click', (e) => {
        console.log(e);
        const [, column] = e.target.href.split('#');
        $('.mobile-hide').hide();
        $(`[x-column=${column}]`).show();
    });

    $('.inning-link').on('click', (e) => {
        e.preventDefault();
        const inning = $(e.target).data('inning');
        $(`#play-by-play [data-inning="${inning}"]`)[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
</script>
@endif
</body>
