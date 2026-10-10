/**
 * AFA Match Center - Client-side Logic (Vanilla JavaScript)
 * Handles Live Clock Ticker, Dynamic Team-Player Dropdown Filtering, and Form Validation.
 */

document.addEventListener('DOMContentLoaded', () => {
    initLiveClock();
    initDynamicPlayerFilter();
    initLoggerValidation();
});

/**
 * Live Clock Ticker
 */
function initLiveClock() {
    const clockContainer = document.getElementById('live-clock-container');
    const minuteDisplay = document.getElementById('match-minute-display');

    if (!clockContainer || !minuteDisplay) return;

    const status = clockContainer.getAttribute('data-status');
    let currentMinute = parseInt(clockContainer.getAttribute('data-initial-minute'), 10) || 0;

    if (status === 'live') {
        setInterval(() => {
            if (currentMinute < 120) {
                currentMinute += 1;
                minuteDisplay.textContent = currentMinute;
            }
        }, 60000);
    }
}

/**
 * Dynamic Player Dropdown Filtering based on selected Team Radio in logger.php
 */
function initDynamicPlayerFilter() {
    const loggerForm = document.getElementById('loggerForm');
    const playerSelect = document.getElementById('player_select');
    const playerNameInput = document.getElementById('player_name');
    const playerIdInput = document.getElementById('player_id');

    if (!loggerForm || !playerSelect) return;

    function filterPlayers() {
        const selectedTeamRadio = loggerForm.querySelector('input[name="team_id"]:checked');
        if (!selectedTeamRadio) return;

        const selectedTeamId = selectedTeamRadio.value;
        const options = playerSelect.querySelectorAll('option');

        let matchCount = 0;
        options.forEach(opt => {
            const teamId = opt.getAttribute('data-team-id');
            if (!teamId) {
                // Keep default prompt and custom option visible
                opt.style.display = 'block';
            } else if (teamId === selectedTeamId) {
                opt.style.display = 'block';
                matchCount++;
            } else {
                opt.style.display = 'none';
            }
        });

        // Reset player select
        playerSelect.value = '';
        if (playerNameInput) playerNameInput.value = '';
        if (playerIdInput) playerIdInput.value = '';
    }

    // Bind team radio change listener
    const teamRadios = loggerForm.querySelectorAll('input[name="team_id"]');
    teamRadios.forEach(radio => {
        radio.addEventListener('change', filterPlayers);
    });

    // Handle player select change
    playerSelect.addEventListener('change', () => {
        const selectedOpt = playerSelect.options[playerSelect.selectedIndex];
        const val = playerSelect.value;

        if (val === 'custom') {
            if (playerNameInput) {
                playerNameInput.value = '';
                playerNameInput.focus();
            }
            if (playerIdInput) playerIdInput.value = '';
        } else if (selectedOpt && val !== '') {
            const name = selectedOpt.getAttribute('data-player-name');
            if (playerNameInput && name) {
                playerNameInput.value = name;
            }
            if (playerIdInput) {
                playerIdInput.value = val;
            }
        }
    });

    // Initial filter run
    filterPlayers();
}

/**
 * Client-side validation handling `onsubmit` for logger.php
 */
function initLoggerValidation() {
    const loggerForm = document.getElementById('loggerForm');
    if (!loggerForm) return;

    loggerForm.addEventListener('submit', (e) => {
        hideValidationError();

        const minuteInput = document.getElementById('minute');
        const playerNameInput = document.getElementById('player_name');
        const eventTypeSelect = document.getElementById('event_type');
        const selectedTeam = loggerForm.querySelector('input[name="team_id"]:checked');

        const errors = [];

        if (!selectedTeam) {
            errors.push('Please select a participating team.');
        }

        const minuteVal = parseInt(minuteInput.value, 10);
        if (isNaN(minuteVal) || minuteInput.value.trim() === '') {
            errors.push('Match minute is required.');
        } else if (minuteVal < 1 || minuteVal > 120) {
            errors.push('Match minute must be a realistic boundary between 1 and 120.');
        }

        const playerNameVal = playerNameInput ? playerNameInput.value.trim() : '';
        if (playerNameVal === '') {
            errors.push('Player name cannot be blank. Select a player or enter a name.');
        }

        const allowedTypes = ['goal', 'yellow_card', 'red_card', 'sub'];
        const eventTypeVal = eventTypeSelect ? eventTypeSelect.value : '';
        if (!eventTypeVal || !allowedTypes.includes(eventTypeVal)) {
            errors.push('Please select a valid event type.');
        }

        if (errors.length > 0) {
            e.preventDefault();
            showValidationError(errors.join(' '));
        }
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
