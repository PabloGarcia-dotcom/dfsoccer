document.addEventListener('DOMContentLoaded', function () {
    var numFixturesInput = document.getElementById('num_fixtures');
    var container = document.getElementById('fixture_fields_container');
    var form = document.getElementById('fixture_selection_form');

    numFixturesInput.addEventListener('input', function () {
        var numFixtures = parseInt(numFixturesInput.value);
        container.innerHTML = '';
        for (var i = 0; i < numFixtures; i++) {
            container.innerHTML += `
                <div class="fixture-fields">
                    <label for="home_club_${i}">Home Club:</label>
                    <select id="home_club_${i}" name="home_club_${i}" class="club-select">
                        ${document.getElementById('club_options').innerHTML}
                    </select>
                    <label for="away_club_${i}">Away Club:</label>
                    <select id="away_club_${i}" name="away_club_${i}" class="club-select">
                        ${document.getElementById('club_options').innerHTML}
                    </select>
                    <label for="fixture_date_${i}">Fixture Date and Time:</label>
                    <input type="datetime-local" id="fixture_date_${i}" name="fixture_date_${i}" min="${new Date().toISOString().slice(0, 16)}" />
                </div>
            `;
        }
        initializeSelect2();
    });

    function initializeSelect2() {
        var selects = document.querySelectorAll('.club-select');
        selects.forEach(function(select) {
            jQuery(select).select2();
        });
    }

    function checkDuplicateClubs() {
        var selects = document.querySelectorAll('.club-select');
        var selectedValues = {};
        var duplicateFound = false;

        selects.forEach(function(select, index) {
            if (select.value) {
                if (selectedValues[select.value]) {
                    duplicateFound = true;
                } else {
                    selectedValues[select.value] = true;
                }

                // Check home and away clubs in the same fixture
                if (index % 2 === 1) {
                    var homeClub = selects[index - 1].value;
                    var awayClub = select.value;
                    if (homeClub && awayClub && homeClub === awayClub) {
                        duplicateFound = true;
                    }
                }
            }
        });

        if (duplicateFound) {
            alert('The same club cannot be selected for home and away, or in different fixtures.');
            return false; // Prevent form submission
        }
        return true; // Allow form submission
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!checkDuplicateClubs()) {
                e.preventDefault(); // Prevent form submission if duplicates are found
            }
        });
    }
});
