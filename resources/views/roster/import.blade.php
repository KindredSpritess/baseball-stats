@extends('layouts.main')
@section('title')
Import Roster
@endsection

@section('content')
<div class="page-container">
    <div class="page-header">
        <h1 class="page-title">Import Roster</h1>
        <p class="page-subtitle">Upload a CSV or Excel file to import players</p>
    </div>

    @if(session('success'))
    <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px 20px; border-radius: 4px; margin-bottom: 20px;">
        {{ session('success') }}
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
                <h3 style="margin-top: 0; font-size: 1.1em;">File Format Guidelines</h3>
                <p style="margin: 10px 0;"><strong>Option 1 - Team/Season in Form:</strong></p>
                <p style="margin: 5px 0 10px 0; font-family: monospace; background: white; padding: 8px; border-radius: 3px;">
                    First Name, Last Name, Number (optional)
                </p>
                <p style="margin: 10px 0;"><strong>Option 2 - Team/Season in File:</strong></p>
                <p style="margin: 5px 0 10px 0; font-family: monospace; background: white; padding: 8px; border-radius: 3px;">
                    First Name, Last Name, Number (optional), Team, Season
                </p>
                <p style="margin: 10px 0 0 0; font-size: 0.9em;">First row should contain column headers and will be skipped during import.</p>
            </div>

            <div style="margin-bottom: 20px;">
                <label for="file" style="display: block; margin-bottom: 5px; font-weight: 600; color: var(--text-primary);">File (CSV or Excel):</label>
                <input type="file" id="file" name="file" accept=".csv,.xlsx,.xls" required style="width: 100%; padding: 10px; border: 1px solid var(--border-light); border-radius: 4px; font-size: 1em;" />
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" id="columns_in_file" name="columns_in_file" value="1" onchange="toggleTeamSeasonFields()" style="margin-right: 10px;" />
                    <span style="font-weight: 600; color: var(--text-primary);">Team and Season columns are in the file</span>
                </label>
                <p style="margin: 5px 0 0 30px; font-size: 0.9em; color: var(--text-secondary);">Check this if your file includes Team and Season columns</p>
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
    const seasons = @json($seasons);
    
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
                season.teams.forEach(team => {
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
