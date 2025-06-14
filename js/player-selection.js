// File: js/player-selection.js
function initPlayerSelection(config) {
    const $ = jQuery;
    const form = $("#" + config.formId);
    const searchInput = $("#" + config.searchId);
    const clubFilter = $("#" + config.clubFilterId);
    const positionFilter = $("#" + config.positionFilterId);
    const priceFilter = $("#" + config.priceFilterId);
    const budget = config.budget;
    const currentPriceElement = $("#" + config.currentPriceId);
    const playersContainer = $("#" + config.playersContainerId);
    const repaginateButton = $("#" + config.repaginateButtonId);
    const itemsPerPage = 10;
    let currentPage = 1;

    // Create pagination container
    const paginationContainer = $("<div>", {id: "pagination"});
    playersContainer.after(paginationContainer);

    function showPage(page) {
        const visibleCards = playersContainer.find(".player-card:not(.filtered-out)");
        const startIndex = (page - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;

        visibleCards.each(function(index) {
            $(this).toggle(index >= startIndex && index < endIndex);
        });
    }

    function updatePagination() {
        const visibleCards = playersContainer.find(".player-card:not(.filtered-out)");
        const totalPages = Math.ceil(visibleCards.length / itemsPerPage);

        paginationContainer.empty();
        for (let i = 1; i <= totalPages; i++) {
            const pageButton = $("<button>", {
                text: i,
                click: function() {
                    currentPage = i;
                    showPage(currentPage);
                    updatePagination();
                },
                disabled: i === currentPage
            });
            paginationContainer.append(pageButton);
        }

        paginationContainer.toggle(totalPages > 1);
    }

    function areFiltersActive() {
        return searchInput.val() !== "" || 
               clubFilter.val() !== "" || 
               positionFilter.val() !== "" || 
               priceFilter.val() !== "";
    }

    function resetFilters() {
        searchInput.val("");
        clubFilter.val("");
        positionFilter.val("");
        priceFilter.val("");
        $(".player-card").show().removeClass("filtered-out");
    }

    repaginateButton.click(function() {
        resetFilters();
        currentPage = 1;
        showPage(currentPage);
        updatePagination();
        $(this).hide();
    });

    form.submit(function(event) {
        const selectedPlayers = $("input[name='selected_players[]']:checked");
        let totalCost = 0;
        selectedPlayers.each(function() {
            totalCost += parseFloat($(this).data("price"));
        });

        if (selectedPlayers.length < 6) {
            event.preventDefault();
            alert("You must select exactly six players.");
        } else if (selectedPlayers.length > 6) {
            event.preventDefault();
            alert("You cannot select more than six players.");
        } else if (totalCost > budget) {
            event.preventDefault();
            alert("You are over budget.");
        }
    });

    function filterPlayers() {
        const searchTerm = searchInput.val().toLowerCase();
        const selectedClub = clubFilter.val();
        const selectedPosition = positionFilter.val();

        $(".player-card").each(function() {
            const card = $(this);
            const playerName = card.find(".player-name").text().toLowerCase();
            const playerClub = card.data("club-id");
            const playerPosition = card.data("position");

            let matchesSearch = playerName.includes(searchTerm);
            let matchesClub = !selectedClub || playerClub === selectedClub;
            let matchesPosition = !selectedPosition || playerPosition === selectedPosition;

            card.toggle(matchesSearch && matchesClub && matchesPosition)
                .toggleClass("filtered-out", !(matchesSearch && matchesClub && matchesPosition));
        });

        if (searchTerm || priceFilter.val()) {
            clubFilter.val("");
            positionFilter.val("");
        }

        if (areFiltersActive()) {
            paginationContainer.hide();
            repaginateButton.show();
        } else {
            paginationContainer.show();
            repaginateButton.hide();
            currentPage = 1;
            showPage(currentPage);
            updatePagination();
        }
    }



    searchInput.on("input", filterPlayers);
    clubFilter.change(filterPlayers);
    positionFilter.change(filterPlayers);
    $("input[name='selected_players[]']").change(updateCurrentPrice);

    priceFilter.on("input", function() {
        var filterValue = parseFloat($(this).val());
        
        $(".player-card").each(function() {
            var price = parseFloat($(this).find("input").data("price"));
            $(this).toggle(isNaN(filterValue) || price <= filterValue)
                   .toggleClass("filtered-out", !isNaN(filterValue) && price > filterValue);
        });

        clubFilter.val("");
        positionFilter.val("");

        if (filterValue) {
            paginationContainer.hide();
            repaginateButton.show();
        } else {
            paginationContainer.show();
            repaginateButton.hide();
            currentPage = 1;
            showPage(currentPage);
            updatePagination();
        }
    });

    // Initial setup
    updateCurrentPrice();
    showPage(currentPage);
    updatePagination();
}