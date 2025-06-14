// Function to update countdown timer display
function updateCountdownTimer(days, hours, minutes, seconds) {
    document.getElementById('dfsoccer-days').innerText = String(days).padStart(2, '0');
    document.getElementById('dfsoccer-hours').innerText = String(hours).padStart(2, '0');
    document.getElementById('dfsoccer-minutes').innerText = String(minutes).padStart(2, '0');
    document.getElementById('dfsoccer-seconds').innerText = String(seconds).padStart(2, '0');
}

// Function to get current time in WordPress timezone
function getCurrentWordPressTime() {
    const now = new Date();
    const localOffset = now.getTimezoneOffset() * 60000; // Convert minutes to milliseconds
    const wpOffset = dfsoccerCountdownData.wpTimezone.offset * 1000; // Convert seconds to milliseconds
    return Math.floor((now.getTime() + localOffset + wpOffset) / 1000); // Return in seconds
}

// Function to start the countdown timer
function startCountdownTimer(firstFixtureDate) {
    function updateTimer() {
        const currentTime = getCurrentWordPressTime();
        const timeRemaining = firstFixtureDate - currentTime;
        
        if (timeRemaining <= 0) {
            clearInterval(countdownInterval);
            const countdownElement = document.getElementById('dfsoccer-countdown');
            if (countdownElement) {
                countdownElement.innerHTML = '<p class="expired-message">Event has started!</p>';
            }
            return;
        }
        
        const days = Math.floor(timeRemaining / (24 * 60 * 60));
        const hours = Math.floor((timeRemaining % (24 * 60 * 60)) / (60 * 60));
        const minutes = Math.floor((timeRemaining % (60 * 60)) / 60);
        const seconds = timeRemaining % 60;
        
        updateCountdownTimer(days, hours, minutes, seconds);
    }

    // Run immediately and then every second
    updateTimer();
    const countdownInterval = setInterval(updateTimer, 1000);
}

// Initialize countdown when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (typeof dfsoccerCountdownData !== 'undefined' && 
        dfsoccerCountdownData.firstFixtureDate && 
        dfsoccerCountdownData.wpTimezone) {
        
        startCountdownTimer(dfsoccerCountdownData.firstFixtureDate);
    } else {
        console.error('Required countdown data is missing', dfsoccerCountdownData);
    }
});