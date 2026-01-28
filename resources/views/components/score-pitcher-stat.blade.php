<td colspan="{{ $inning['width'] }}">
    <table style="border-collapse:collapse;width:100%;table-layout:fixed"><tr>
    @foreach ($inning['pitching'] as $p)
    <td @style(['border: none', 'border-left: 2px solid blue' => !$loop->first])>
        <table style="border-collapse:collapse;width:100%;table-layout:fixed"><tr>
            @foreach(explode(' / ', $p[$stat] ?? '0') as $part)
            <td @style(['border: none', 'border-left: 1px solid black' => !$loop->first])>{{ $part }}</td>
            @endforeach
        </tr></table>
    </td>
    @endforeach
    </tr></table>
</td>