@extends('layouts.main')
@section('title')
Create New Team
@endsection

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Create New Team</h1>
        <p class="page-subtitle">Add a new team to the system</p>
    </div>

    <div class="stats-section">
        <form action="{{route('teamstore')}}" method="POST" style="max-width: 500px;">
            @csrf
            <div style="margin-bottom: 20px;">
                <label for="name" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Team Name:</label>
                <input id="name" name="name" autocomplete="off" style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;" />
            </div>
            <div style="margin-bottom: 20px;">
                <label for="short_name" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Short Name:</label>
                <input id="short_name" name="short_name" autocomplete="off" style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;" />
            </div>
            <div style="margin-bottom: 30px;">
                <label for="season" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Season:</label>
                <input id="season" name="season" value="{{ request()->get('season') }}" style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;" />
            </div>
            <div>
                <button type="submit" style="background: var(--primary); color: var(--white); border: none; padding: 12px 24px; border-radius: 4px; font-size: 1em; font-weight: 600; cursor: pointer; transition: background 0.2s ease;">Create Team</button>
            </div>
        </form>
    </div>
</div>
@endsection