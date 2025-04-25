document.addEventListener('DOMContentLoaded', function() {
    // Team selector functionality
    const teamBtns = document.querySelectorAll('.team-btn');
    const homeTeamAdd = document.getElementById('home-team-add');
    const awayTeamAdd = document.getElementById('away-team-add');
    const lineupAddContainer = document.querySelector('.lineup-add-container');

    // Function to update UI based on hash
    function updateFromHash() {
        const hash = window.location.hash;

        // Check if we should show the lineup add component
        if (hash.includes('add-player')) {
            lineupAddContainer.style.display = 'block';

            // Determine which team to show
            let teamToShow = 'home'; // Default to home

            if (hash.includes('team=away')) {
                teamToShow = 'away';
            }

            // Update active button
            teamBtns.forEach(btn => {
                if (btn.dataset.team === teamToShow) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });

            // Show corresponding team section
            if (teamToShow === 'home') {
                homeTeamAdd.style.display = 'block';
                awayTeamAdd.style.display = 'none';
            } else {
                homeTeamAdd.style.display = 'none';
                awayTeamAdd.style.display = 'block';
            }
        } else {
            // Hide the lineup add component if not explicitly shown
            lineupAddContainer.style.display = 'none';
        }
    }

    // Initial update from hash
    updateFromHash();

    // Listen for hash changes
    window.addEventListener('hashchange', updateFromHash);

    if (teamBtns.length > 0) {
        teamBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const team = this.dataset.team;

                // Update hash without full page reload
                const currentHash = window.location.hash;
                let newHash = '#add-player&team=' + team;

                // Preserve other hash parameters if they exist
                if (currentHash.includes('&') && !currentHash.includes('team=')) {
                    const otherParams = currentHash.split('&').slice(1).join('&');
                    newHash = newHash + '&' + otherParams;
                }

                window.location.hash = newHash;

                // Update UI (will be handled by hashchange event)
            });
        });
    }

    // Close button functionality
    document.querySelectorAll('.close-btn').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            // Remove the hash or reset to default hash
            window.location.hash = '';
        });
    });

    // Player search and autocomplete functionality
    const playerSearchInputs = document.querySelectorAll('.player-search');

    playerSearchInputs.forEach(playerSearchInput => {
        const playerForm = playerSearchInput.closest('.add-player-form');
        const playerNumberInput = playerForm.querySelector('.player-number');
        const positionButtons = playerForm.querySelectorAll('.position-btn');
        const playerPositionInput = playerForm.querySelector('.player-position-input');
        const selectedPositionText = playerForm.querySelector('.selected-position span');
        const playerSuggestions = playerForm.querySelector('.suggestions-container');
        const teamShortName = playerForm.querySelector('.team-short-name').value;
        const teamId = playerForm.querySelector('.team-id').value;
        const action = playerForm.getAttribute('action');
        const token = playerForm.querySelector('input[name="_token"]').value;

        let selectedPerson = null;
        let debounceTimer;

        // Function to fetch player suggestions
        function fetchPlayerSuggestions(query) {
            if (query.length < 2) {
                playerSuggestions.style.display = 'none';
                return;
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetch(`/api/players/search?query=${encodeURIComponent(query)}&team_id=${teamId}`)
                    .then(response => response.json())
                    .then(data => {
                        playerSuggestions.innerHTML = '';

                        if (data.length === 0) {
                            const noResults = document.createElement('div');
                            noResults.className = 'suggestion-item';
                            noResults.textContent = 'No players found';
                            playerSuggestions.appendChild(noResults);
                        } else {
                            data.forEach(person => {
                                const item = document.createElement('div');
                                item.className = 'suggestion-item';
                                if (person.played_for_team) {
                                    item.className += ' team-player';
                                }
                                item.textContent = `${person.lastName}, ${person.firstName}`;

                                item.addEventListener('click', () => {
                                    selectedPerson = person;
                                    playerSearchInput.value = `${person.lastName}, ${person.firstName}`;
                                    playerSuggestions.style.display = 'none';
                                });

                                playerSuggestions.appendChild(item);
                            });
                        }

                        playerSuggestions.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error fetching player suggestions:', error);
                    });
            }, 300);
        }

        // Event listener for input changes
        playerSearchInput.addEventListener('input', function() {
            fetchPlayerSuggestions(this.value);
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target !== playerSearchInput && e.target !== playerSuggestions) {
                playerSuggestions.style.display = 'none';
            }
        });

        // Position button click handlers
        positionButtons.forEach(button => {
            button.addEventListener('click', function() {
                const position = this.dataset.position;

                // Update active button
                positionButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                // Update hidden input and display text
                playerPositionInput.value = position;
                selectedPositionText.textContent = this.textContent;
            });
        });

        // Keyboard shortcuts for positions
        document.addEventListener('keydown', function(e) {
            // Only process if we're focused on this form
            if (!playerForm.contains(document.activeElement)) {
                return;
            }

            // Number keys 1-9 for positions 1-9
            if (e.key >= '1' && e.key <= '9') {
                const position = e.key;
                const button = playerForm.querySelector(`.position-btn[data-position="${position}"]`);
                if (button) {
                    button.click();
                    e.preventDefault();
                }
            }
        });

        playerForm.querySelector(`.position-btn[data-position="EH"]`).click();

        // Form submission
        playerForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!selectedPerson && !playerSearchInput.value.includes(',')) {
                alert('Please select a player from the suggestions');
                return;
            }

            if (!playerPositionInput.value) {
                alert('Please select a position');
                return;
            }

            // Format the play string for the gamelog route
            // Format: @TEAM LastName, FirstName #Number: Position
            const playerName = selectedPerson ? `${selectedPerson.lastName}, ${selectedPerson.firstName}` : playerSearchInput.value;
            const playerNumber = playerNumberInput.value ? ` #${playerNumberInput.value}` : '';
            const position = playerPositionInput.value ? `: ${playerPositionInput.value}` : '';

            // Submit the form using AJAX
            $.ajax(action, {
                accepts: {
                    gamestate: 'application/json'
                },
                data: {
                    'play': `@${teamShortName} ${playerName}${playerNumber}${position}`,
                    '_token': token,
                },
                method: 'PUT'
            }).then(() => {
                // Reload the page but preserve the hash
                location.reload();
            });
        });
    });
});
