<h1>Create New Team</h1>
<form action="{{route('teamstore')}}" method="POST">
    @csrf
    <div>
        <label for="name">Name:</label>
        <input id="name" name="name" autocomplete="off" />
    </div>
    <div>
        <label for="short_name">Short Name:</label>
        <input type="short_name" name="short_name" autocomplete="off" />
    </div>
    <div>
        <label for="season">Season:</label>
        <input id="season" name="season" />
    </div>
    <div>
        <button>Create Team</button>
    </div>
</form>