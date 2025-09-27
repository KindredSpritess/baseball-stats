@foreach ($calendars as $calendar)
    <a href="/schedules/{{ basename($calendar) }}" target="_blank">{{ basename($calendar) }}</a><br>
@endforeach