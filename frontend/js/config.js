/**
 * Automatic base path configuration for local and GitHub Pages deployment
 * Detects environment and sets appropriate base path
 */
(function() {
  // Detect environment
  const isLocal = window.location.hostname === 'localhost' ||
                  window.location.hostname === '127.0.0.1' ||
                  window.location.hostname === '';

  // Set appropriate base path
  const basePath = isLocal ? '/' : '/CS444-NewHorizon/';

  // Create and insert <base> tag if it doesn't exist
  if (!document.querySelector('base')) {
    const base = document.createElement('base');
    base.href = basePath;
    document.head.insertBefore(base, document.head.firstChild);
  }

  console.log('Environment:', isLocal ? 'Local' : 'Production');
  console.log('Base path:', basePath);
})();
