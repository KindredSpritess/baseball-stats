@extends('layouts.main')
@section('title')
Create New Season
@endsection

@section('content')
<div class="page-container welcome-container">
    <div class="page-header">
        <h1 class="section-title">Create New Season</h1>
        <p class="page-subtitle">Add a new season to the system</p>
    </div>

    <div class="stats-section">
        <form action="{{route('season.store')}}" method="POST" style="max-width: 500px;">
            @csrf
            <div style="margin-bottom: 20px;">
                <label for="name" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Season Name:</label>
                <input id="name" name="name" autocomplete="off" value="{{ old('name') }}" style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;" />
                @error('name')
                <span style="color: red; font-size: 0.9em;">{{ $message }}</span>
                @enderror
            </div>
            <div style="display: flex; gap: 12px;">
                <button type="submit" style="background: var(--primary); color: var(--white); border: none; padding: 12px 24px; border-radius: 4px; font-size: 1em; font-weight: 600; cursor: pointer; transition: background 0.2s ease;">Create Season</button>
            </div>
        </form>
    </div>
</div>
@endsection
