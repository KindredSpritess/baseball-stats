<canvas id="{{ $id }}" class="pie-chart"></canvas>
<script>
    new Chart("{{ $id }}", {
        type: "pie",
        data: {
            labels: {!! $data->keys()->values()->toJson() !!},
            datasets: [{
                backgroundColor: {!! $data->pluck('color')->toJson() !!},
                data: {!! $data->pluck('value')->toJson() !!},
            }],
        },
        options: {
            title: {
                display: true,
                text: "{{ $title }}"
            }
        }
    });
</script>