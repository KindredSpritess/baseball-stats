@extends('layouts.main')
@section('title')
{{ $team->name }}
@endsection

@section('head')
<style>
.team-page {
  --team-primary: {{ $team->primary_color ?: '#1e88ea' }};
  --team-secondary: {{ $team->secondary_color ?: '#ffffff' }};
}

.team-page .welcome-title {
  background: linear-gradient(135deg, var(--team-primary) 0%, var(--team-secondary) 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  text-shadow: none;
}

.team-page .section-title {
  border-bottom: 3px solid var(--team-primary);
}

.team-page .inline-link {
  color: var(--team-primary);
}

.team-page .inline-link:hover {
  color: var(--team-primary);
  opacity: 0.8;
}

.team-page table.stats-table thead td, .team-page table.stats-table tfoot td {
    background-color: var(--team-primary);
    color: var(--white);
}
</style>
@endsection

@section('content')
<div class="welcome-container team-page">
    <h1 class="welcome-title">{{ $team->name }} - {{ $team->season }}</h1>
    <p class="page-subtitle">Edit the team details</p>

    <div class="stats-section">
        <form action="{{route('team.update', ['team' => $team->id])}}" method="POST" style="max-width: 500px;">
            @csrf
            <div style="margin-bottom: 20px;">
                <label for="name" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Team Name:</label>
                <input id="name" name="name" value="{{ $team->name }}" autocomplete="off" style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;" />
            </div>
            <div style="margin-bottom: 20px;">
                <label for="short_name" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Short Name:</label>
                <input id="short_name" name="short_name" value="{{ $team->short_name }}" autocomplete="off" style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;" disabled />
            </div>
            <div style="margin-bottom: 30px;">
                <label for="season" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Season:</label>
                <input id="season" name="season" value="{{ $team->season }}" style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;" disabled />
            </div>
            <div style="margin-bottom: 30px;">
                <label for="primary_color" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Primary Color:</label>
                <input id="primary_color" name="primary_color" value="{{ $team->primary_color }}" type="color" />
            </div>
            <div style="margin-bottom: 30px;">
                <label for="secondary_color" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Secondary Color:</label>
                <input id="secondary_color" name="secondary_color" value="{{ $team->secondary_color }}" type="color" />
            </div>
            <div>
                <button type="submit" style="background: var(--primary); color: var(--white); border: none; padding: 12px 24px; border-radius: 4px; font-size: 1em; font-weight: 600; cursor: pointer; transition: background 0.2s ease;">Update Team</button>
            </div>
        </form>
    </div>
</div>
@endsection