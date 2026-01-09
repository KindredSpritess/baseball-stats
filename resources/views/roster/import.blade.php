@extends('layouts.main')
@section('title')
Import Roster
@endsection

@section('content')
<div class="page-container welcome-container">
    <div class="page-header">
        <h1 class="section-title">Import Roster</h1>
        <p class="page-subtitle">Upload a CSV or Excel file to import players</p>
    </div>

    @if(session('success'))
    <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px 20px; border-radius: 4px; margin-bottom: 20px;">
        {{ session('success') }}
    </div>
    @endif

    @if(session('warning'))
    <div style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 12px 20px; border-radius: 4px; margin-bottom: 20px;">
        {{ session('warning') }}
    </div>
    @endif

    @if(session('error'))
    <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px 20px; border-radius: 4px; margin-bottom: 20px;">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px 20px; border-radius: 4px; margin-bottom: 20px;">
        <ul style="margin: 0; padding-left: 20px;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="stats-section">
        <form action="{{ route('roster.import.process') }}" method="POST" enctype="multipart/form-data" style="max-width: 600px;">
            @csrf

            <div style="background: #e7f3ff; border: 1px solid #b3d9ff; color: #004085; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                <h3 style="margin-top: 0; font-size: 1.1em;">Import Options</h3>
                <p style="margin: 10px 0;"><strong>Option 1 - Upload File:</strong> CSV or Excel file with player data</p>
                <p style="margin: 10px 0;"><strong>Option 2 - Import from URL:</strong> MyGameDay roster URL or CSV/Excel file URL</p>
                <p style="margin: 10px 0 0 0; font-size: 0.9em;">Example URL: https://websites.mygameday.app/team_info.cgi?c=0-13003-0-655996-27257243&amp;a=PLAYERS</p>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="file" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">File (CSV or Excel):</label>
                <input type="file" id="file" name="file" accept=".csv,.xlsx,.xls" style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;" onchange="clearUrl()" />
            </div>

            <div style="text-align: center; margin: 20px 0; color: var(--text-secondary); font-weight: 600;">
                - OR -
            </div>

            <div style="margin-bottom: 20px;">
                <label for="url" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">URL:</label>
                <input type="url" id="url" name="url" placeholder="https://websites.mygameday.app/team_info.cgi?..." style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;" onchange="clearFile()" />
                <p style="margin: 5px 0 0 0; font-size: 0.9em; color: var(--text-secondary);">Enter a URL to a roster page or CSV/Excel file</p>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" id="columns_in_file" name="columns_in_file" value="1" onchange="toggleTeamSeasonFields()" style="margin-right: 10px;" />
                    <span style="font-weight: 600; color: var(--text-primary);">Team and Season columns are in the file/URL</span>
                </label>
                <p style="margin: 5px 0 0 30px; font-size: 0.9em; color: var(--text-secondary);">Check this if your data includes Team and Season columns</p>
            </div>

            <div id="team-season-fields">
                <div style="margin-bottom: 20px;">
                    <label for="season_id" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Season:</label>
                    <select id="season_id" name="season_id" onchange="updateTeamOptions()" style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;">
                        <option value="">Select a season</option>
                        @foreach($seasons as $season)
                            <option value="{{ $season->id }}">{{ $season->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom: 30px;">
                    <label for="team_id" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">Team:</label>
                    <select id="team_id" name="team_id" style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;">
                        <option value="">Select a team</option>
                    </select>
                </div>
            </div>

            <div>
                <button type="submit" style="background: var(--primary); color: var(--white); border: none; padding: 12px 24px; border-radius: 4px; font-size: 1em; font-weight: 600; cursor: pointer; transition: background 0.2s ease;">Import Roster</button>
            </div>
        </form>
    </div>
</div>

<script>
    const seasons = JSON.parse('@json($seasons)');

    function clearFile() {
        const fileInput = document.getElementById('file');
        fileInput.value = '';
    }

    function clearUrl() {
        const urlInput = document.getElementById('url');
        urlInput.value = '';
    }

    function toggleTeamSeasonFields() {
        const checkbox = document.getElementById('columns_in_file');
        const fields = document.getElementById('team-season-fields');
        const seasonSelect = document.getElementById('season_id');
        const teamSelect = document.getElementById('team_id');

        if (checkbox.checked) {
            fields.style.display = 'none';
            seasonSelect.required = false;
            teamSelect.required = false;
        } else {
            fields.style.display = 'block';
            seasonSelect.required = true;
            teamSelect.required = true;
        }
    }

    function updateTeamOptions() {
        const seasonSelect = document.getElementById('season_id');
        const teamSelect = document.getElementById('team_id');
        const selectedSeasonId = parseInt(seasonSelect.value);

        teamSelect.innerHTML = '<option value="">Select a team</option>';

        if (selectedSeasonId) {
            const season = seasons.find(s => s.id === selectedSeasonId);
            if (season && season.teams) {
                season.teams.sort((a, b) => a.name.localeCompare(b.name)).forEach(team => {
                    const option = document.createElement('option');
                    option.value = team.id;
                    option.textContent = team.name;
                    teamSelect.appendChild(option);
                });
            }
        }
    }
</script>
@endsection
