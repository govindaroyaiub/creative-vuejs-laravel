/**
 * Global Timezone Initialization
 * This file should be imported in the main app.js to initialize timezone detection
 */

import timezoneDetector from './utils/timezone.js';

// Initialize timezone detection when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    timezoneDetector.init();
});

// Export for manual usage
export default timezoneDetector;
