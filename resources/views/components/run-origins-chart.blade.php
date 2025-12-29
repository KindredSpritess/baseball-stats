<x-pie-chart :id="$id" :data="collect([
    'Walks / HBP' => ['value' => $walks, 'color' => '#2b5797'],
    'Hits' => ['value' => $hits, 'color' => '#1e7145'],
    'Errors' => ['value' => $errors, 'color' => '#FFCE56'],
])" title="Run Origins" />