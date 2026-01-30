<td @class([
    'play-quadrant',
    "play-$colour",
    "pinch-runner" => $pinchRunner
])><span @class(['play-circled' => $circled]) @style(["padding:{$padding} 1px" => $circled])>{!! $play !!}</span></td>