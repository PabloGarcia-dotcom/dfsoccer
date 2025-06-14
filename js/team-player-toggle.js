document.addEventListener("DOMContentLoaded", function() {
    var teamRows = document.querySelectorAll(".team-row");
    teamRows.forEach(function(row) {
        row.addEventListener("click", function() {
            var nextRow = row.nextElementSibling;
            if (nextRow.classList.contains("player-details")) {
                nextRow.style.display = nextRow.style.display === "none" ? "table-row" : "none";
            }
        });
    });
});