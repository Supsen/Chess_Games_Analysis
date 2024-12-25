let players = []; // To store all players
let filteredPlayers = []; // To store filtered players

async function fetchPlayers() {
    try {
        const response = await fetch('get_players.php');
        if (!response.ok) {
            throw new Error('Failed to fetch players data.');
        }

        players = await response.json();
        filteredPlayers = players; // Initially, all players are displayed
        renderTable(); // Render the table with all players
    } catch (error) {
        console.error('Error fetching players:', error);
        const tableBody = document.querySelector('#playersTable tbody');
        tableBody.innerHTML = '<tr><td colspan="6">Error loading players data.</td></tr>';
    }
}

// Render the table based on the filtered players
function renderTable() {
    const tableBody = document.querySelector('#playersTable tbody');
    tableBody.innerHTML = ''; // Clear the table

    filteredPlayers.forEach((player, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${index + 1}</td>
            <td><img src="${player.ProfileUrl || './images/profile-icon/profile-icon.jpeg'}" 
                     alt="${player.Username}'s profile" class="profile-img"></td>
            <td>${player.Username}</td>
            <td>${player.Country}</td>
            <td>${player.Elo}</td>
            <td>
                <div class="dropdown">
                    <button class="dropdown-button">...</button>
                    <div class="dropdown-content">
                        <a href="player-insight.html?playerId=${player.PlayerID}" target="_blank">Insight</a>
                        <a href="#?playerId=${player.PlayerID}" target="_blank">Profile</a>
                    </div>
                </div>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

// Filter players based on the search query
function searchPlayers(event) {
    const query = event.target.value.toLowerCase();
    filteredPlayers = players.filter(player =>
        player.Username.toLowerCase().includes(query)|| 
        player.Country.toLowerCase().includes(query)
    );
    renderTable(); // Re-render the table with the filtered players
}

// Automatically call fetchPlayers when this script is loaded
document.addEventListener('DOMContentLoaded', () => {
    fetchPlayers();
    const searchBox = document.getElementById('searchBox'); // Get the search box
    searchBox.addEventListener('input', searchPlayers); // Add input event listener for search
});

// Filter Player by their Elo and Country Function
function filterPlayers() {
    const eloFilter = document.getElementById('eloFilter').value;
    const countryFilter = document.getElementById('countryFilter').value;

    // Start with all players
    filteredPlayers = [...players];

    // Apply Elo sorting
    if (eloFilter === 'lowToHigh') {
        filteredPlayers.sort((a, b) => a.Elo - b.Elo);
    } else if (eloFilter === 'highToLow') {
        filteredPlayers.sort((a, b) => b.Elo - a.Elo);
    }

    // Apply Country filter
    if (countryFilter) {
        filteredPlayers = filteredPlayers.filter(player =>
            player.Country === countryFilter
        );
    }

    renderTable(); // Re-render the table with filtered data
}

// Add event listener to the filter button
document.getElementById('applyFilter').addEventListener('click', filterPlayers);