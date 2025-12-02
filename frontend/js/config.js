/**
 * NEW HORIZON - Configuration
 * Auto-detects environment and sets correct paths
 */

const CONFIG = (function() {
  const hostname = window.location.hostname;
  const pathname = window.location.pathname;

  // Detect environment
  let env = 'local';
  let basePath = '';
  let apiBase = '';

  if (hostname.includes('cis444.cs.csusm.edu')) {
    // School server
    env = 'school';
    basePath = '/group8/frontend';
    apiBase = '/group8/backend/api';
  } else if (hostname.includes('github.io')) {
    // GitHub Pages - extract repo name from path
    const repoMatch = pathname.match(/^\/([^\/]+)/);
    const repoName = repoMatch ? repoMatch[1] : '';
    env = 'github';
    basePath = repoName ? `/${repoName}` : '';
    apiBase = ''; // GitHub Pages is static, no backend API
  } else if (hostname === 'localhost' || hostname === '127.0.0.1') {
    // Local development
    env = 'local';
    basePath = '';
    apiBase = '/backend/api';
  } else {
    // Default / other hosting
    env = 'production';
    basePath = '';
    apiBase = '/backend/api';
  }

  return {
    ENV: env,
    BASE_PATH: basePath,
    API_BASE: apiBase,

    // Helper to build asset paths
    asset: function(path) {
      return this.BASE_PATH + (path.startsWith('/') ? path : '/' + path);
    },

    // Helper to build page paths
    page: function(path) {
      return this.BASE_PATH + '/pages/' + path;
    },

    // Helper to build API paths
    api: function(endpoint) {
      return this.API_BASE + endpoint;
    }
  };
})();

// Make available globally
window.CONFIG = CONFIG;

console.log('Environment:', CONFIG.ENV, '| Base:', CONFIG.BASE_PATH);
