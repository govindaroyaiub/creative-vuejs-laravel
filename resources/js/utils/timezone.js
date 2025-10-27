/**
 * Timezone Detection Utility
 * Automatically detects user's timezone and sends it to the server
 */

export class TimezoneDetector {
    constructor() {
        this.timezone = null;
        this.init();
    }

    /**
     * Initialize timezone detection
     */
    init() {
        this.detectTimezone();
        this.sendTimezoneToServer();
    }

    /**
     * Detect user's timezone using JavaScript
     */
    detectTimezone() {
        try {
            // Get timezone from browser
            this.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

            // Store in localStorage for persistence
            localStorage.setItem('user_timezone', this.timezone);
        } catch (error) {
            console.warn('Failed to detect timezone:', error);
            // Fallback to UTC
            this.timezone = 'UTC';
        }
    }

    /**
     * Get the detected timezone
     */
    getTimezone() {
        return this.timezone || localStorage.getItem('user_timezone') || 'UTC';
    }

    /**
     * Send timezone to server via AJAX
     */
    async sendTimezoneToServer() {
        if (!this.timezone) return;

        try {
            await fetch('/api/set-timezone', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'X-Timezone': this.timezone,
                },
                body: JSON.stringify({
                    timezone: this.timezone,
                }),
            });
        } catch (error) {
            console.warn('Failed to send timezone to server:', error);
        }
    }

    /**
     * Format time with detected timezone
     */
    formatTime(date, options = {}) {
        const defaultOptions = {
            timeZone: this.getTimezone(),
            year: 'numeric',
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        };

        return new Intl.DateTimeFormat('en-US', { ...defaultOptions, ...options }).format(new Date(date));
    }

    /**
     * Get current time in detected timezone
     */
    getCurrentTime() {
        return this.formatTime(new Date());
    }

    /**
     * Configure Axios to always send timezone header
     */
    configureAxios(axios) {
        axios.defaults.headers.common['X-Timezone'] = this.getTimezone();
    }
}

// Create and export a singleton instance
export const timezoneDetector = new TimezoneDetector();

// Auto-initialize on import
export default timezoneDetector;
