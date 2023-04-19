<head>
    <script src="/jquery.min.js"></script>
    <link rel="stylesheet" href="/styles.css" />
    <title>{{ $game->away_team->short_name }} @ {{ $game->home_team->short_name }}</title>
</head>
<body>
<table id='game-view'>
    <tr style="max-height: 100%;">
        <td>
            <x-box-score :team="$game->away_team" :lineup="$game->lineup[0]" />
        </td>
        <td style='text-align: center; width: 100%;'>
            <h2>{{ $game->firstPitch }} at {{ $game->location }}</h2>
            <h3>{{ implode(' - ', $game->score) }}</h3>
            <p>
                @if ($game->half)
                    ⬇️
                @else
                    ⬆️
                @endif
                {{ $game->inning }}
                ({{ $game->balls }} - {{ $game->strikes }}) {{ $game->outs }} outs
            </p>
            <svg height="440px" width="440px" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 448.125 448.125" xml:space="preserve" fill="#00000000" stroke="#00000000">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier"> 
                    <g transform="translate(0 -1020.36)" shape-rendering="auto" image-rendering="auto" color-rendering="auto" color-interpolation="sRGB">
                        <path style="fill:#5A3392;" d="M224.109,1020.36c-51.375,0-104.961,18.027-145.844,50.219 c-37.233,29.316-61.724,68.332-77.844,120.094c-0.785,2.639-0.16,5.494,1.656,7.563l109.188,124.438l12.625-10.813l14.875,17.377 l-12.406,10.623l17,19.406l19.25-16.469l14.875,17.344l-19.031,16.313l21.719,24.75c-2.605,5.889-4.125,12.355-4.125,19.188 c0,26.418,21.648,48.094,48.063,48.094s47.969-21.678,47.969-48.094c0-6.807-1.486-13.25-4.063-19.125l21.812-24.875l-18.937-16.25 l14.875-17.344l19.156,16.377l17-19.406l-12.312-10.531l14.875-17.377l12.531,10.721l109.031-124.344 c1.816-2.068,2.442-4.924,1.656-7.563c-15.25-51.344-40.622-90.785-77.844-120.094c-40.883-32.192-94.374-50.219-145.75-50.219 L224.109,1020.36z">
                        </path>
                        <path style="fill:#FB9761;" d="M224.097,1183.03l121.063,105.998l-86.344,98.502c-8.749-9.23-21.048-15.064-34.719-15.064 c-13.677,0-26.001,5.83-34.781,15.064l-86.375-98.502L224.097,1183.03z"></path>
                        <path style="fill:#4bb33d;" d="M224.097,1036.435c47.618,0,98.056,16.903,135.844,46.655 c33.519,26.396,55.683,62.189,70.281,108.906l-74.531,85l-126.375-110.623c-1.632-1.404-3.761-2.092-5.906-1.906 c-1.706,0.125-3.327,0.793-4.625,1.906L92.409,1276.997l-74.531-85c15.391-47.24,36.813-82.553,70.281-108.906 C125.946,1053.337,176.479,1036.434,224.097,1036.435L224.097,1036.435z"></path>
                        <path style="fill:#4bb33d;" d="M224.088,1201.415l99.295,86.941l-70.819,80.791c-7.176-7.57-17.264-12.354-28.476-12.354 c-11.217,0-21.326,4.783-28.528,12.354l-70.844-80.791L224.088,1201.415z"></path>
                        <g>
                            <path style="fill:#FB9761;" d="M224.097,1260.374c8.931,0,15.969,7.131,15.969,16.063c0,8.932-7.037,15.938-15.969,15.938 c-8.931,0-16.062-7.006-16.062-15.938C208.034,1267.505,215.165,1260.374,224.097,1260.374z"></path>
                            <path style="fill:#FB9761;" d="M224.097,1388.403c17.768,0,32.031,14.232,32.031,32s-14.263,32.031-32.031,32.031 s-32-14.264-32-32.031S206.329,1388.403,224.097,1388.403L224.097,1388.403z"></path>
                        </g>
                        <path style="fill:#4bb33d;" d="M224.09,1053.436c40.888,0,84.197,14.514,116.644,40.063c28.782,22.666,47.813,53.402,60.348,93.516 l-63.997,72.986l-108.514-94.988c-1.401-1.207-3.229-1.799-5.072-1.641c-1.465,0.107-2.857,0.684-3.971,1.641l-108.514,94.988 l-63.997-72.986c13.216-40.563,31.61-70.887,60.348-93.516C139.811,1067.95,183.202,1053.436,224.09,1053.436L224.09,1053.436z">                        </path>
                        @if ($game->fielding('1'))
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif" x=224 y=1280 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('1')->person->lastName }}, {{ $game->fielding('1')->person->firstName[0] }}</text>
                        @endif
                        @if ($game->fielding('2'))
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=224 y=1455 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('2')->person->lastName }}, {{ $game->fielding('2')->person->firstName[0] }}</text>
                        @endif
                        @if ($game->fielding('3'))
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=344 y=1330 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('3')->person->lastName }}, {{ $game->fielding('3')->person->firstName[0] }}</text>
                        @endif
                        @if ($game->fielding('4'))
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=284 y=1230 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('4')->person->lastName }}, {{ $game->fielding('4')->person->firstName[0] }}</text>
                        @endif
                        @if ($game->fielding('5'))
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=104 y=1330 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('5')->person->lastName }}, {{ $game->fielding('5')->person->firstName[0] }}</text>
                        @endif
                        @if ($game->fielding('6'))
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=164 y=1230 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('6')->person->lastName }}, {{ $game->fielding('6')->person->firstName[0] }}</text>
                        @endif
                        @if ($game->fielding('7'))
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=104 y=1150 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('7')->person->lastName }}, {{ $game->fielding('7')->person->firstName[0] }}</text>
                        @endif
                        @if ($game->fielding('8'))
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=224 y=1100 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('8')->person->lastName }}, {{ $game->fielding('8')->person->firstName[0] }}</text>
                        @endif
                        @if ($game->fielding('9'))
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=344 y=1150 font-weight="bold" fill="white" stroke="black" stroke-width="1px">{{ $game->fielding('9')->person->lastName }}, {{ $game->fielding('9')->person->firstName[0] }}</text>
                        @endif
                        @if ($game->hitting())
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=224 y=1425 font-weight="bold" fill="black" stroke="white" stroke-width="1px">{{ $game->hitting()->person->lastName }}, {{ $game->hitting()->person->firstName[0] }}</text>
                        @endif
                        @if ($game->bases[0])
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=344 y=1300 font-weight="bold" fill="black" stroke="white" stroke-width="1px">{{ $game->bases['0']->person->lastName }}</text>
                        @endif
                        @if ($game->bases[1])
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=224 y=1200 font-weight="bold" fill="black" stroke="white" stroke-width="1px">{{ $game->bases['1']->person->lastName }}</text>
                        @endif
                        @if ($game->bases[2])
                        <text text-anchor="middle" font-size="x-large" font-family="sans-serif"  x=104 y=1300 font-weight="bold" fill="black" stroke="white" stroke-width="1px">{{ $game->bases['2']->person->lastName }}</text>
                        @endif
                    </g>
                </g>
            </svg>
            <textarea id='plays' style="width:100%" rows="20">
@foreach ($game->plays as $play)
{{ $play->play }}
@endforeach
</textarea>
            <button id='submitPlays'>Update From Start</button>
            <form id='log' action="">
                <input type="hidden" name="play" id="gamelog" />
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
                        <th><input id='first' autocomplete='off' /></th>
                        <th><input id='second' autocomplete='off' /></th>
                        <th><input id='third' autocomplete='off' /></th>
                    </tr>
                </table>
                <input type="submit" />
            </form>
            <div id='play-by-play'>
                @foreach ($game->plays as $play)
                    @if ($play->human)
                    <div>{{ $play->human }}</div>
                    @endif
                    @if ($play->game_event)
                    <div class='game-event'>{{ $play->game_event }}</div>
                    @endif
                @endforeach
            </div>
        </td>
        <td>
            <x-box-score :team="$game->home_team" :lineup="$game->lineup[1]" />
        </td>
    </tr>
</table>
<script>
    $('#log').on('submit', (event) => {
        event.preventDefault();
        const parts = [];
        if ($('#third').val()) {
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
    });
</script>
</body>