<td @class([
    'play-quadrant',
    "play-{$lines[0]->colour}",
    "pinch-runner" => $lines[0]->pinchRunner
])>
    @php
        $lineCount = count($lines);
        $fontSize = match(true) {
            $lineCount <= 1 => '1em',
            $lineCount <= 2 => '0.7em',
            $lineCount <= 3 => '0.5em',
            $lineCount <= 4 => '0.3em',
            $lineCount <= 5 => '0.6em',
            default => '0.5em'
        };
    @endphp
    @foreach ($lines as $line)
    <span @class(['play-circled' => $line->circled])
        @style([
            "padding:{$line->padding} 1px" => $line->circled,
            "margin: 0 {$line->margin}" => $line->circled,
            "font-size: {$fontSize}" => true,
            "display: block" => true,
            "border-top: 1px solid black" => !$loop->first,
        ])
    >
        {!! $line->play !!}
    </span>
    @endforeach
</td>