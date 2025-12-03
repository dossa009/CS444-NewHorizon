/**
 * NEW HORIZON - Main JavaScript
 * Loads partials and handles auth state
 */

// Load header and footer partials
async function loadPartials() {
  const header = document.querySelector('header[data-partial="header"]');
  const footer = document.querySelector('footer[data-partial="footer"]');

  const basePath = window.CONFIG ? CONFIG.BASE_PATH : '/group8/frontend';

  if (header) {
    try {
      const res = await fetch(basePath + '/partials/header.html');
      if (res.ok) {
        const html = await res.text();
        header.outerHTML = html;
      }
    } catch (e) { console.error('Header load error:', e); }
  }

  if (footer) {
    try {
      const res = await fetch(basePath + '/partials/footer.html');
      if (res.ok) {
        const html = await res.text();
        footer.outerHTML = html;
      }
    } catch (e) { console.error('Footer load error:', e); }
  }

  // Update auth buttons after loading header
  updateAuthButtons();
}

// Update login/logout buttons based on auth state
function updateAuthButtons() {
  const token = localStorage.getItem('auth_token');
  const userStr = localStorage.getItem('current_user');

  const loginBtn = document.getElementById('login-button-container');
  const accountBtn = document.getElementById('account-button-container');
  const adminBtn = document.getElementById('admin-button-container');
  const logoutBtn = document.getElementById('logout-button-container');

  if (!loginBtn) return;

  if (token && userStr) {
    try {
      const user = JSON.parse(userStr);
      loginBtn.style.display = 'none';
      accountBtn.style.display = 'inline-block';
      logoutBtn.style.display = 'inline-block';
      adminBtn.style.display = user.role === 'admin' ? 'inline-block' : 'none';
    } catch (e) {
      loginBtn.style.display = 'inline-block';
      accountBtn.style.display = 'none';
      adminBtn.style.display = 'none';
      logoutBtn.style.display = 'none';
    }
  } else {
    loginBtn.style.display = 'inline-block';
    accountBtn.style.display = 'none';
    adminBtn.style.display = 'none';
    logoutBtn.style.display = 'none';
  }
}

// Handle logout
function handleLogout() {
  localStorage.removeItem('auth_token');
  localStorage.removeItem('current_user');
  window.location.href = '/group8/frontend/index.html';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', loadPartials);
