<div id="{{ $id }}" class="pie-chart"></div>
<script>
    google.charts.load('current', {'packages':['corechart']});
    google.charts.setOnLoadCallback(() => {
        const data = google.visualization.arrayToDataTable([
            ['Label', 'Value'],
            @foreach($data as $key => $item)
            ['{{ $key }}', {{ $item['value'] }}],
            @endforeach
        ]);

        const options = {
            title: '{{ $title }}',
            colors: {!! $data->pluck('color')->toJson() !!}
        };

        const chart = new google.visualization.PieChart(document.getElementById('{{ $id }}'));
        chart.draw(data, options);
    });
</script>