document.addEventListener("DOMContentLoaded", function() {
    var selectedPlayers = window.redDivData.selectedPlayers || [];
    var players = window.redDivData.players || [];
    var playerPositions = {};
    var positionPlayers = {}; // Reverse mapping: position → playerId
    
    // Debug helper function
    function logState(message) {
        console.log(message, {
            selectedPlayers: [...selectedPlayers],
            playerPositions: {...playerPositions},
            positionPlayers: {...positionPlayers}
        });
    }
    
    function initializePositions() {
        playerPositions = {};
        positionPlayers = {};
        
        selectedPlayers.forEach(function(playerId, index) {
            if (index < 6) {
                var position = index + 1;
                playerPositions[playerId] = position;
                positionPlayers[position] = playerId;
            }
        });
    }
    
    function updatePlayerCards() {
        for (var i = 1; i <= 6; i++) {
            var playerCard = document.getElementById("card_for_player_" + i);
            var playerName = document.getElementById("player_name_" + i);
            
            if (playerCard) {
                playerCard.classList.remove("selected");
                playerCard.dataset.playerId = '';
                playerCard.style.cursor = 'default';
                playerCard.title = '';
            }
            if (playerName) {
                playerName.textContent = "";
            }
            
            var playerId = positionPlayers[i];
            if (playerId) {
                var player = players.find(p => p.id == playerId);
                if (player) {
                    if (playerCard) {
                        playerCard.classList.add("selected");
                        playerCard.dataset.playerId = player.id;
                        playerCard.style.cursor = 'pointer';
                        playerCard.title = 'Click to unselect player';
                    }
                    if (playerName) {
                        playerName.textContent = player.name;
                    }
                }
            }
        }
    }
    
    function findPlayerCheckbox(playerId) {
        return document.querySelector(`input[name="selected_players[]"][value="${playerId}"]`);
    }
    
    function ensureCheckboxesMatchSelectedPlayers() {
        // First uncheck all checkboxes
        var allCheckboxes = document.querySelectorAll(`input[name="selected_players[]"]`);
        allCheckboxes.forEach(function(checkbox) {
            // Temporarily remove event listener to prevent triggering handleCheckboxChange
            var newCheckbox = checkbox.cloneNode(true);
            checkbox.parentNode.replaceChild(newCheckbox, checkbox);
            newCheckbox.checked = false;
        });
        
        // Then check only those in our selectedPlayers array
        selectedPlayers.forEach(function(playerId) {
            var checkbox = findPlayerCheckbox(playerId);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
        
        // Re-add event listeners
        setupCheckboxListeners();
    }
    
   function unselectPlayer(playerId) {
    console.log("Unselecting player:", playerId);

    var position = playerPositions[playerId];

    // Update our internal state first
    delete playerPositions[playerId];

    if (position) {
        delete positionPlayers[position];
    }

    // Remove from selectedPlayers array
    selectedPlayers = selectedPlayers.filter(id => id !== playerId);
    
    // Update the global variable
    window.selectedPlayers = selectedPlayers;

    // Update the checkbox - do this last to avoid triggering the change event during our own updates
    var checkbox = findPlayerCheckbox(playerId);
    if (checkbox) {
        // Replace to remove listeners first
        var newCheckbox = checkbox.cloneNode(true);
        checkbox.parentNode.replaceChild(newCheckbox, checkbox);
        newCheckbox.checked = false;

        // Re-add the listener
        newCheckbox.addEventListener("change", handleCheckboxChange);
    }

    // Update the UI
    updatePlayerCards();
    
    // Call updateCurrentPrice directly
    if (typeof window.updateCurrentPrice === "function") {
        window.updateCurrentPrice();
    } else if (typeof updateCurrentPrice === "function") {
        updateCurrentPrice(selectedPlayers);
    }

    console.log("updateCurrentPrice function exists:", typeof updateCurrentPrice === "function");
    
    // Call updateCurrentPrice to refresh the total price
    if (typeof updateCurrentPrice === "function") {
        updateCurrentPrice(selectedPlayers);
    }

    logState("After unselecting player");
}
    
    function addCardClickHandlers() {
        for (var i = 1; i <= 6; i++) {
            var playerCard = document.getElementById("card_for_player_" + i);
            if (playerCard) {
                var newCard = playerCard.cloneNode(true);
                playerCard.parentNode.replaceChild(newCard, playerCard);
                playerCard = newCard;
                
                playerCard.addEventListener('click', function() {
                    var playerId = this.dataset.playerId;
                    if (playerId) {
                        unselectPlayer(playerId);
                    }
                });
            }
        }
    }
function handleCheckboxChange(event) {
    console.log("Checkbox changed:", event.target.value, "checked:", event.target.checked);
    
    var playerId = event.target.value;
    
    if (event.target.checked) {
        if (!selectedPlayers.includes(playerId)) {
            selectedPlayers.push(playerId);
        }
    } else {
        selectedPlayers = selectedPlayers.filter(id => id !== playerId);
    }
    
    // Make sure we have no duplicates
    selectedPlayers = [...new Set(selectedPlayers)];
    
    // Set the global selectedPlayers array
    window.selectedPlayers = selectedPlayers;
    
    console.log("Selected Players:", selectedPlayers);
    
    // Update price if the function exists
    try {
        if (typeof window.updateCurrentPrice === "function") {
            console.log("Calling global updateCurrentPrice function");
            window.updateCurrentPrice();
        } else {
            console.log("Global updateCurrentPrice function not available");
            
            // Fallback to local function if it exists
            if (typeof updateCurrentPrice === "function") {
                updateCurrentPrice(selectedPlayers);
            }
        }
    } catch (e) {
        console.error("Error updating price:", e);
    }
    
    // Update positions and UI
    initializePositions();
    updatePlayerCards();
    logState("After checkbox change");
}
    
    function setupCheckboxListeners() {
        var form = document.getElementById("player_selection_form");
        if (form) {
            // First, get all checkboxes and clone them to remove existing listeners
            var checkboxes = form.querySelectorAll("input[name='selected_players[]']");
            checkboxes.forEach(function(checkbox) {
                var newCheckbox = checkbox.cloneNode(true);
                checkbox.parentNode.replaceChild(newCheckbox, checkbox);
                
                // Add direct event listener to each checkbox
                newCheckbox.addEventListener("change", handleCheckboxChange);
            });
        }
    }
    
    // Ensure form data is correct before submission
    var form = document.getElementById("player_selection_form");
    if (form) {
        form.addEventListener("submit", function(event) {
            console.log("Form submitting with selectedPlayers:", selectedPlayers);
            
            // Validate before submission
            const selectedCount = selectedPlayers.length;
            if (selectedCount !== 6) {
                event.preventDefault();
                alert("You must select exactly six players.");
                return false;
            }
            
            // Ensure hidden field with final selection is added
            var hiddenField = document.createElement("input");
            hiddenField.type = "hidden";
            hiddenField.name = "final_player_selection";
            hiddenField.value = JSON.stringify(selectedPlayers);
            form.appendChild(hiddenField);
            
            // Let the form submit if validation passes
            return true;
        });
    }
    
function initialize() {
    // Make sure selectedPlayers matches checkbox state
    var checkboxes = document.querySelectorAll("input[name='selected_players[]']");
    selectedPlayers = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);
    
    // Remove duplicates from selectedPlayers
    selectedPlayers = [...new Set(selectedPlayers)];
    
    // Make selectedPlayers globally accessible by attaching to window
    window.selectedPlayers = selectedPlayers;
    
    initializePositions();
    updatePlayerCards();
    setupCheckboxListeners();
    addCardClickHandlers();
    
    // Log initial state
    console.log("Initial selectedPlayers:", selectedPlayers);
    logState("After initialization");
    
    // Call updateCurrentPrice explicitly after everything is initialized
    if (typeof window.updateCurrentPrice === 'function') {
        console.log("Calling updateCurrentPrice with:", window.selectedPlayers);
        window.updateCurrentPrice();
    } else {
        console.log("updateCurrentPrice function not available yet");
    }
}
    
    // Run initialization
    initialize();
    
    // Add styles
    const style = document.createElement('style');
    style.textContent = `
        .card_for_soccer {
            transition: all 0.2s ease-in-out;
        }
        .card_for_soccer.selected {
            border-color: #3498db;
            background-color: rgba(255, 255, 0, 1);
        }
        .card_for_soccer.selected:hover {
            transform: scale(1.05);
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            opacity: 0.8;
        }
    `;
    document.head.appendChild(style);
    
    // Expose key functions to global scope for testing/debugging
    window.reinitializeSoccerField = initialize;
    window.getSelectedPlayers = function() {
        return [...selectedPlayers]; // Return a copy
    };
});

// Add this function to your second code to ensure checkbox state matches player selection
function syncCheckboxesWithSelection() {
    // Get the form
    var form = document.getElementById("player_selection_form");
    if (!form) return;
    
    // Get all checkboxes
    var checkboxes = form.querySelectorAll("input[name='selected_players[]']");
    
    // First uncheck all
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = false;
    });
    
    // Then check only those in selectedPlayers array
    selectedPlayers.forEach(function(playerId) {
        var checkbox = document.querySelector(`input[name="selected_players[]"][value="${playerId}"]`);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
    
    console.log("Synchronized checkboxes with selectedPlayers:", selectedPlayers);
}

// Update the form submit handler to ensure checkboxes are synced
document.getElementById("player_selection_form").addEventListener("submit", function(event) {
    // Ensure checkboxes match the selectedPlayers array
    syncCheckboxesWithSelection();
    
    // Now double-check what's actually checked to validate
    const checkedBoxes = document.querySelectorAll("input[name='selected_players[]']:checked");
    const selectedCount = checkedBoxes.length;
    
    console.log("Form submitting with " + selectedCount + " players selected");
    
    // Validate player count
    if (selectedCount !== 6) {
        event.preventDefault();
        alert("You must select exactly six players.");
        return false;
    }
    
    // Validate budget - use the existing budget validation code
    let totalCost = 0;
    checkedBoxes.forEach(player => {
        totalCost += parseFloat(player.getAttribute("data-price") || 0);
    });
    
    if (totalCost > budget) {
        event.preventDefault();
        alert("You are over budget.");
        return false;
    }
    
    // Let form submit
    return true;
});



// Call this whenever you update selectedPlayers in your second function
// For example, add it to the end of your player selection function
// 
// 
// 
// Add this function at the end of your existing red-div.js file, right before the closing });
