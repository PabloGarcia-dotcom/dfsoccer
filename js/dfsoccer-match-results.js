document.addEventListener("DOMContentLoaded", function() {
    const playerSearchInput = document.getElementById("player_search");
    const resultsSearchInput = document.getElementById("results_search");

    if (playerSearchInput) {
        playerSearchInput.addEventListener("input", function() {
            const searchTerm = playerSearchInput.value.toLowerCase();
            const playerCards = document.querySelectorAll(".player-card");

            playerCards.forEach(card => {
                const playerName = card.querySelector(".player-name").textContent.toLowerCase();
                if (playerName.includes(searchTerm)) {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
        });
    }

    if (resultsSearchInput) {document.addEventListener("DOMContentLoaded", function() {
    const playerSearchInput = document.getElementById("player_search");
    const resultsSearchInput = document.getElementById("results_search");

    if (playerSearchInput) {
        playerSearchInput.addEventListener("input", function() {
            const searchTerm = playerSearchInput.value.toLowerCase();
            const playerCards = document.querySelectorAll(".player-card");

            playerCards.forEach(card => {
                const playerName = card.querySelector(".player-name").textContent.toLowerCase();
                if (playerName.includes(searchTerm)) {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
        });
    }

    if (resultsSearchInput) {
        resultsSearchInput.addEventListener("input", function() {
            const searchTerm = resultsSearchInput.value.toLowerCase();
            const resultEntries = document.querySelectorAll(".result-entry");

            resultEntries.forEach(entry => {
                const playerName = entry.querySelector(".result-player-name").textContent.toLowerCase();
                if (playerName.includes(searchTerm)) {
                    entry.style.display = "block";
                } else {
                    entry.style.display = "none";
                }
            });
        });
    }

    // Check if the form was just submitted
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('form_submitted') === 'true') {
        // Remove the query parameter
        urlParams.delete('form_submitted');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', newUrl);
        
        // Reload the page once
        if (!sessionStorage.getItem('reloaded')) {
            sessionStorage.setItem('reloaded', 'true');
            location.reload();
        } else {
            sessionStorage.removeItem('reloaded');
        }
    }
});
        resultsSearchInput.addEventListener("input", function() {
            const searchTerm = resultsSearchInput.value.toLowerCase();
            const resultEntries = document.querySelectorAll(".result-entry");

            resultEntries.forEach(entry => {
                const playerName = entry.querySelector(".result-player-name").textContent.toLowerCase();
                if (playerName.includes(searchTerm)) {
                    entry.style.display = "block";
                } else {
                    entry.style.display = "none";
                }
            });
        });
    }

    // Check if the form was just submitted
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('form_submitted') === 'true') {
        // Remove the query parameter
        urlParams.delete('form_submitted');
        const newUrl = window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
        window.history.replaceState({}, '', newUrl);
        
        // Reload the page once
        if (!sessionStorage.getItem('reloaded')) {
            sessionStorage.setItem('reloaded', 'true');
            location.reload();
        } else {
            sessionStorage.removeItem('reloaded');
        }
    }
});

