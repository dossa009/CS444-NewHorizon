/**
 * NEW HORIZON - Configuration centralisée
 * Tous les paths et URLs en un seul endroit
 */

// Détection de l'environnement
const ENV = {
  isDevelopment: window.location.hostname === 'localhost' ||
                 window.location.hostname === '127.0.0.1' ||
                 window.location.hostname === '',
  isProduction: window.location.hostname.includes('github.io') ||
                window.location.hostname.includes('csusm.edu')
};

// Configuration des paths selon l'environnement
const CONFIG = {
  // Base URL pour les assets frontend (CSS, JS, images)
  BASE_PATH: ENV.isDevelopment ? '/' : '/CS444-NewHorizon/',

  // URL de l'API backend
  API_URL: ENV.isDevelopment ? 'http://localhost:8000/api' : '/CS444-NewHorizon/api',

  // Paths des ressources frontend
  PATHS: {
    CSS: ENV.isDevelopment ? '/frontend/css/' : '/CS444-NewHorizon/frontend/css/',
    JS: ENV.isDevelopment ? '/frontend/js/' : '/CS444-NewHorizon/frontend/js/',
    IMAGES: ENV.isDevelopment ? '/frontend/public/assets/' : '/CS444-NewHorizon/frontend/public/assets/',
    PARTIALS: ENV.isDevelopment ? '/frontend/partials/' : '/CS444-NewHorizon/frontend/partials/',
    PAGES: ENV.isDevelopment ? '/frontend/pages/' : '/CS444-NewHorizon/frontend/pages/'
  },

  // Routes des pages
  ROUTES: {
    HOME: 'index.html',
    LOGIN: 'pages/login.html',
    ADMIN: 'pages/admin.html',
    ACCOUNT: 'pages/account.html',
    RESOURCES: 'pages/resources.html',
    EXERCISES: 'pages/exercises.html',
    FORUM: 'pages/forum.html',
    CALENDAR: 'pages/calendar.html',
    OPPORTUNITIES: 'pages/opportunities.html',
    ABOUT: 'pages/about.html',
    CONTACT: 'pages/contact.html'
  },

  // Endpoints API
  API_ENDPOINTS: {
    AUTH: {
      LOGIN: '/auth/login',
      REGISTER: '/auth/register',
      LOGOUT: '/auth/logout',
      ME: '/auth/me',
      CHANGE_PASSWORD: '/auth/change-password'
    },
    ADMIN: {
      STATS: '/admin/dashboard/stats',
      USERS: '/admin/users',
      AUDIT_LOG: '/admin/audit-log'
    }
  }
};

// Fonctions helpers pour construire les URLs
const PathHelpers = {
  // Construire une URL complète pour un asset
  asset(relativePath) {
    return CONFIG.BASE_PATH + relativePath.replace(/^\//, '');
  },

  // Construire une URL d'API complète
  api(endpoint) {
    return CONFIG.API_URL + (endpoint.startsWith('/') ? endpoint : '/' + endpoint);
  },

  // Construire une URL de page
  page(pageName) {
    const route = CONFIG.ROUTES[pageName.toUpperCase()] || pageName;
    return CONFIG.BASE_PATH + route;
  },

  // Obtenir le chemin d'un partial
  partial(name) {
    return CONFIG.PATHS.PARTIALS + name + (name.endsWith('.html') ? '' : '.html');
  },

  // Obtenir le chemin d'un fichier CSS
  css(name) {
    return CONFIG.PATHS.CSS + name + (name.endsWith('.css') ? '' : '.css');
  },

  // Obtenir le chemin d'un fichier JS
  js(name) {
    return CONFIG.PATHS.JS + name + (name.endsWith('.js') ? '' : '.js');
  }
};

// Exporter la configuration
window.APP_CONFIG = CONFIG;
window.PATH = PathHelpers;
window.ENV = ENV;

// Log de la configuration en développement
if (ENV.isDevelopment) {
  console.log('🔧 New Horizon Configuration:');
  console.log('Environment:', ENV.isDevelopment ? 'Development' : 'Production');
  console.log('API URL:', CONFIG.API_URL);
  console.log('Base Path:', CONFIG.BASE_PATH);
}
