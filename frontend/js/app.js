/**
 * NEW HORIZON - Main JavaScript
 * Loads partials and handles auth state
 */

// Load header and footer partials
async function loadPartials() {
  const header = document.querySelector('header[data-partial="header"]');
  const footer = document.querySelector('footer[data-partial="footer"]');

  const basePath = window.CONFIG ? CONFIG.BASE_PATH : '';

  if (header) {
    try {
      const res = await fetch(basePath + '/partials/header.html');
      if (res.ok) {
        let html = await res.text();
        // Replace {{BASE_PATH}} placeholders with actual path
        html = html.replace(/\{\{BASE_PATH\}\}/g, basePath);
        header.innerHTML = html;
      }
    } catch (e) { console.error('Header load error:', e); }
  }

  if (footer) {
    try {
      const res = await fetch(basePath + '/partials/footer.html');
      if (res.ok) {
        let html = await res.text();
        html = html.replace(/\{\{BASE_PATH\}\}/g, basePath);
        footer.innerHTML = html;
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
      accountBtn.style.display = 'block';
      logoutBtn.style.display = 'block';
      adminBtn.style.display = user.role === 'admin' ? 'block' : 'none';
    } catch (e) {
      loginBtn.style.display = 'block';
      accountBtn.style.display = 'none';
      adminBtn.style.display = 'none';
      logoutBtn.style.display = 'none';
    }
  } else {
    loginBtn.style.display = 'block';
    accountBtn.style.display = 'none';
    adminBtn.style.display = 'none';
    logoutBtn.style.display = 'none';
  }
}

// Handle logout
function handleLogout() {
  localStorage.removeItem('auth_token');
  localStorage.removeItem('current_user');
  const basePath = window.CONFIG ? CONFIG.BASE_PATH : '';
  window.location.href = basePath + '/index.html';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', loadPartials);

// Calendar (if on calendar page)
const calendarBody = document.getElementById('calendar-body');
const monthYearLabel = document.getElementById('month-year');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');

if (calendarBody && monthYearLabel) {
  const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  let currentDate = new Date();

  function renderCalendar() {
    const month = currentDate.getMonth();
    const year = currentDate.getFullYear();
    monthYearLabel.textContent = `${months[month]} ${year}`;

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    calendarBody.innerHTML = '';
    let date = 1;

    for (let i = 0; i < 6; i++) {
      const row = document.createElement('tr');
      for (let j = 0; j < 7; j++) {
        const cell = document.createElement('td');
        if (i === 0 && j < firstDay) {
          // empty
        } else if (date > daysInMonth) {
          break;
        } else {
          cell.textContent = date++;
        }
        row.appendChild(cell);
      }
      calendarBody.appendChild(row);
    }
  }

  if (prevBtn) prevBtn.onclick = () => { currentDate.setMonth(currentDate.getMonth() - 1); renderCalendar(); };
  if (nextBtn) nextBtn.onclick = () => { currentDate.setMonth(currentDate.getMonth() + 1); renderCalendar(); };
  renderCalendar();
}
