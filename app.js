/**
 * AFA Match Center - Client-side Logic (Vanilla JavaScript)
 * Handles Live Match Clock Ticker and Logger Form Client-side Validation.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Live Match Clock Ticker
    initLiveClock();

    // 2. Initialize Client-Side Form Validation for Official Logger
    initLoggerValidation();
});

/**
 * Increments the live match clock on the DOM at regular intervals
 * if match status is 'live'.
 */
function initLiveClock() {
    const clockContainer = document.getElementById('live-clock-container');
    const minuteDisplay = document.getElementById('match-minute-display');

    if (!clockContainer || !minuteDisplay) return;

    const status = clockContainer.getAttribute('data-status');
    let currentMinute = parseInt(clockContainer.getAttribute('data-initial-minute'), 10) || 0;

    // Only increment automatically if match is live
    if (status === 'live') {
        // Football clock ticker: tick minute every 60 seconds (60000 ms)
        // For dynamic UI demonstration, tick every 30 seconds or 60 seconds
        const CLOCK_INTERVAL_MS = 60000; 

        setInterval(() => {
            if (currentMinute < 120) {
                currentMinute += 1;
                minuteDisplay.textContent = currentMinute;
                
                // Add subtle highlight pulse effect
                minuteDisplay.classList.add('minute-pulse');
                setTimeout(() => {
                    minuteDisplay.classList.remove('minute-pulse');
                }, 1000);
            }
        }, CLOCK_INTERVAL_MS);
    }
}

/**
 * Client-side validation handling `onsubmit` for logger.php
 */
function initLoggerValidation() {
    const loggerForm = document.getElementById('loggerForm');
    const alertBox = document.getElementById('js-validation-alert');
    const alertMsg = document.getElementById('js-validation-message');

    if (!loggerForm) return;

    loggerForm.addEventListener('submit', (e) => {
        // Clear previous error states
        hideValidationError();

        const minuteInput = document.getElementById('minute');
        const playerNameInput = document.getElementById('player_name');
        const eventTypeSelect = document.getElementById('event_type');
        const selectedTeam = loggerForm.querySelector('input[name="team_id"]:checked');

        const errors = [];

        // Check Team Selection
        if (!selectedTeam) {
            errors.push('Please select a participating team.');
        }

        // Check Minute Input
        const minuteVal = parseInt(minuteInput.value, 10);
        if (isNaN(minuteVal) || minuteInput.value.trim() === '') {
            errors.push('Match minute is required.');
        } else if (minuteVal < 1 || minuteVal > 120) {
            errors.push('Match minute must be a realistic boundary between 1 and 120.');
        }

        // Check Player Name Input
        const playerNameVal = playerNameInput ? playerNameInput.value.trim() : '';
        if (playerNameVal === '') {
            errors.push('Player name cannot be blank.');
        } else if (playerNameVal.length < 2) {
            errors.push('Player name must be at least 2 characters long.');
        }

        // Check Event Type Input
        const allowedTypes = ['goal', 'yellow_card', 'red_card', 'sub'];
        const eventTypeVal = eventTypeSelect ? eventTypeSelect.value : '';
        if (!eventTypeVal || !allowedTypes.includes(eventTypeVal)) {
            errors.push('Please select a valid event type.');
        }

        // If validation errors exist, halt submission and display error message
        if (errors.length > 0) {
            e.preventDefault();
            showValidationError(errors.join(' '));
            
            // Highlight first invalid element
            if (isNaN(minuteVal) || minuteVal < 1 || minuteVal > 120) {
                minuteInput.focus();
                minuteInput.classList.add('input-error');
            } else if (playerNameVal === '') {
                playerNameInput.focus();
                playerNameInput.classList.add('input-error');
            }
        }
    });

    // Clear error highlights on input change
    const inputs = loggerForm.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('input', () => {
            input.classList.remove('input-error');
            hideValidationError();
        });
    });
}

function showValidationError(message) {
    const alertBox = document.getElementById('js-validation-alert');
    const alertMsg = document.getElementById('js-validation-message');
    if (alertBox && alertMsg) {
        alertMsg.textContent = message;
        alertBox.style.display = 'flex';
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function hideValidationError() {
    const alertBox = document.getElementById('js-validation-alert');
    if (alertBox) {
        alertBox.style.display = 'none';
    }
}
