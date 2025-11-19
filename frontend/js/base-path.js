/**
 * Base Path Configuration
 * This file MUST be loaded FIRST before any other scripts or stylesheets
 * It dynamically sets the base href for GitHub Pages deployment
 */

(function() {
  // Check if we're on GitHub Pages
  const isGitHubPages = window.location.hostname.includes('github.io');

  if (isGitHubPages) {
    // Create and inject base tag
    const base = document.createElement('base');
    base.href = '/CS444-NewHorizon/';
    document.head.insertBefore(base, document.head.firstChild);

    console.log('✅ Base path set for GitHub Pages:', base.href);
  } else {
    console.log('🏠 Running in local/development mode');
  }
})();
