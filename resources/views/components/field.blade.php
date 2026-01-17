
<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg" viewBox="-40 -40 527.94775 528.12701" xml:space="preserve" fill="#00000000" stroke="#00000000">
    <style>
        svg circle:hover, polygon:hover {
            stroke: red;
            stroke-width: 3;
            z-index: 1000;
            r: 10;
        }

        svg polygon, svg circle {
            stroke: black;
            stroke-width: 1;
        }
    </style>
    <g shape-rendering="auto" image-rendering="auto" color-rendering="auto" color-interpolation="sRGB">
        <!-- Border -->
        <path style="fill:#d89b75" d="m 224.02066,0
                                      c -51.375,0 -104.961,18.027 -145.843997,50.219
                                      c -37.233,29.316 -61.724,68.332 -77.84399963,120.094
                                      c -0.785,2.639 -0.16,30.431 1.65600003,32.5
                                      L 180.08366,380.845
                                      c -2.605,5.889 -4.125,12.355 -4.125,19.188 0,26.418 21.648,48.094 48.063,48.094 26.415,0 47.969,-21.678 47.969,-48.094 0,-6.807 -1.486,-13.25 -4.063,-19.125
                                      l 178.031,-178.031
                                      c 1.816,-2.068 2.442,-29.922 1.656,-32.561 -15.25,-51.344 -40.622,-90.785 -77.844,-120.094 -40.883,-32.192 -94.374,-50.219 -145.75,-50.219
                                      z" />
        <!-- <path style="fill:#eeeeee" d="m 224.00866,159.639
                                      l 121.063,121.187
                                      l -86.344,86.344
                                      c -8.749,-9.23 -21.048,-15.064 -34.719,-15.064
                                      c -13.677,0 -26.001,5.83 -34.781,15.064
                                      l -86.375,-86.375 z" /> -->
        <path style="fill:#ffffff" d="m 224.00866,159.639
                                      l 121.063,121.187
                                      l -121.063,121.187
                                      l -121.063,-121.187 z" />
                                      <!-- l 121.063,-121.187
                                      l -121.063,121.187 z" /> -->
        <!-- Border around the border. -->
        <path style="fill:none;stroke:#000000;stroke-width:2" d="m 224.02066,1 c -50.375,0 -103.961,17.027 -144.843997,49.219 -37.233,29.316 -61.724,68.332 -77.84399963,120.094 -0.785,2.639 -0.16,30.431 1.65600003,32.5 L 180.08366,381.845 c -2.605,5.889 -4.125,12.355 -4.125,19.188 0,26.418 21.648,48.094 48.063,48.094 26.415,0 47.969,-21.678 47.969,-48.094 0,-6.807 -1.486,-13.25 -4.063,-19.125 l 178.031,-178.031 c 1.816,-2.068 2.442,-29.922 1.656,-32.561 -15.25,-51.344 -40.622,-90.785 -77.844,-120.094 -40.883,-32.192 -94.374,-49.219 -145.75,-49.219 z" />
        <!-- Outfield -->
        <path style="fill:#75d89b" d="m 224.00866,16.075
                                      c 47.618,0 98.056,16.903 135.844,46.655 33.519,26.396 63.19668,78.8981 77.79468,125.6151
                                      l -60.29468,59.7909
                                      A 224.009 376.014 0 0 0 71.518069,249.4604 
                                      L 7.1171623,185.0595
                                      C 22.508162,137.8195 54.602663,89.084 88.070663,62.731 125.85766,32.977 176.39066,16.074 224.00866,16.075
                                      Z" />
        <!-- Infield -->
        <path style="fill:#75d89b" d="m 223.99966,181.055
                                      l 99.295,99.771 
                                      l -99.295,99.771
                                      l -99.295,-99.771 z" />
        <!-- Mound -->
        <path style="fill:#d89b75" d="m 224.009,246.014 c 8.931,0 15.969,7.131 15.969,16.063 0,8.932 -7.037,15.938 -15.969,15.938 -8.931,0 -16.062,-7.006 -16.062,-15.938 -0.001,-8.932 7.13,-16.063 16.062,-16.063 z" />
        <!-- Batter's Box -->
        <path style="fill:#d89b75" d="m 224.009,368.043 c 17.768,0 32.031,14.232 32.031,32 0,17.768 -14.263,32.031 -32.031,32.031 -17.768,0 -32,-14.264 -32,-32.031 0,-17.767 14.232,-32 32,-32 z" />
    </g>

    @foreach ($ballsInPlay as $ball)
        @php
            $shape = match($ball->type) {
                'B' => 'downward-pointing double-triangle',
                'G' => 'downward-pointing triangle',
                'F' => 'diamond',
                'L' => 'star',
                'P' => 'triangle',
                default => 'circle'
            };
            $color = match($ball->result) {
                'O' => '#9e9e9e',
                '1B' => '#4caf50',
                '2B' => '#2196f3',
                '3B' => '#ff9800',
                'HR' => '#ffd700',
                default => '#9e9e9e'
            };
        @endphp
        @if($shape === 'circle')
            <circle cx="{{ $ball->position[0] }}" cy="{{ $ball->position[1] }}" r="7" fill="{{ $color }}">
                <title>{{ $ball->play->human }}</title>
            </circle>
        @elseif($shape === 'triangle')
            <polygon points="{{ $ball->position[0] }},{{ $ball->position[1] - 8 }} {{ $ball->position[0] - 7 }},{{ $ball->position[1] + 6 }} {{ $ball->position[0] + 7 }},{{ $ball->position[1] + 6 }}" fill="{{ $color }}">
                <title>{{ $ball->play->human }}</title>
            </polygon>
        @elseif($shape === 'downward-pointing triangle')
            <polygon points="{{ $ball->position[0] }},{{ $ball->position[1] + 8 }} {{ $ball->position[0] - 7 }},{{ $ball->position[1] - 6 }} {{ $ball->position[0] + 7 }},{{ $ball->position[1] - 6 }}" fill="{{ $color }}">
                <title>{{ $ball->play->human }}</title>
            </polygon>
        @elseif($shape === 'downward-pointing double-triangle')
            <polygon points="{{ $ball->position[0] }},{{ $ball->position[1] + 8 }} {{ $ball->position[0] - 7 }},{{ $ball->position[1] - 6 }} {{ $ball->position[0] + 7 }},{{ $ball->position[1] - 6 }}" fill="{{ $color }}">
                <title>{{ $ball->play->human }}</title>
            </polygon>
            <polygon points="{{ $ball->position[0] }},{{ $ball->position[1] + 5 }} {{ $ball->position[0] - 7 }},{{ $ball->position[1] - 9 }} {{ $ball->position[0] + 7 }},{{ $ball->position[1] - 9 }}" fill="{{ $color }}">
                <title>{{ $ball->play->human }}</title>
            </polygon>
        @elseif($shape === 'diamond')
            <polygon points="{{ $ball->position[0] }},{{ $ball->position[1] - 8 }} {{ $ball->position[0] + 7 }},{{ $ball->position[1] }} {{ $ball->position[0] }},{{ $ball->position[1] + 8 }} {{ $ball->position[0] - 7 }},{{ $ball->position[1] }}" fill="{{ $color }}">
                <title>{{ $ball->play->human }}</title>
            </polygon>
        @elseif($shape === 'star')
            <polygon points="{{ $ball->position[0] }},{{ $ball->position[1] - 8 }} {{ $ball->position[0] + 2 }},{{ $ball->position[1] - 3 }} {{ $ball->position[0] + 8 }},{{ $ball->position[1] - 3 }} {{ $ball->position[0] + 3 }},{{ $ball->position[1] + 1 }} {{ $ball->position[0] + 5 }},{{ $ball->position[1] + 7 }} {{ $ball->position[0] }},{{ $ball->position[1] + 3 }} {{ $ball->position[0] - 5 }},{{ $ball->position[1] + 7 }} {{ $ball->position[0] - 3 }},{{ $ball->position[1] + 1 }} {{ $ball->position[0] - 8 }},{{ $ball->position[1] - 3 }} {{ $ball->position[0] - 2 }},{{ $ball->position[1] - 3 }}" fill="{{ $color }}">
                <title>{{ $ball->play->human }}</title>
            </polygon>
        @endif
    @endforeach

    <!-- Legend -->
    <g transform="translate(355, 350)">
        <rect x="-5" y="-5" width="95" height="100" fill="white" fill-opacity="0.9" stroke="black" stroke-width="1" rx="5"/>

        <!-- Shapes by Type -->
        <polygon points="5,4 10,10 5,16 0,10" fill="none" stroke="black" stroke-width="2"/>
        <text fill="black" x="15" y="15" font-family="Arial" font-size="13">Fly Ball</text>

        <polygon points="5,25 10,35 0,35" fill="none" stroke="black" stroke-width="2"/>
        <text fill="black" x="15" y="35" font-family="Arial" font-size="13">Pop Up</text>


        <polygon points="5,42 7,47 13,47 8,51 10,57 5,53 0,57 2,51 -2,47 3,47" fill="none" stroke="black" stroke-width="2"/>
        <text fill="black" x="15" y="55" font-family="Arial" font-size="13">Line Drive</text>

        <polygon points="5,75 10,65 0,65" fill="none" stroke="black" stroke-width="2"/>
        <text fill="black" x="15" y="75" font-family="Arial" font-size="13">Ground Ball</text>
    </g>

    <g transform="translate(8, 350)">
        <rect x="-5" y="-5" width="77" height="90" fill="white" fill-opacity="0.9" stroke="black" stroke-width="1" rx="5"/>

        <!-- Colors by Result -->
        <circle cx="5" cy="10" r="5" fill="#9e9e9e"/>
        <text fill="black" x="15" y="14" font-family="Arial" font-size="11">Out</text>

        <circle cx="5" cy="25" r="5" fill="#4caf50"/>
        <text fill="black" x="15" y="28" font-family="Arial" font-size="11">Single</text>

        <circle cx="5" cy="40" r="5" fill="#2196f3"/>
        <text fill="black" x="15" y="43" font-family="Arial" font-size="11">Double</text>

        <circle cx="5" cy="55" r="5" fill="#ff9800"/>
        <text fill="black" x="15" y="58" font-family="Arial" font-size="11">Triple</text>

        <circle cx="5" cy="70" r="5" fill="#ffd700"/>
        <text fill="black" x="15" y="73" font-family="Arial" font-size="11">Home Run</text>
    </g>
</svg>