/**
 * NEW HORIZON - Main JavaScript
 * Handles navigation, partial injection, and page interactions
 */

// Get base path based on environment
function getBasePath() {
  const isLocal = window.location.hostname === 'localhost' ||
                  window.location.hostname === '127.0.0.1' ||
                  window.location.hostname === '';
  return isLocal ? '/' : '/CS444-NewHorizon/';
}

// Inject HTML partials (header, footer)
async function injectPartial(selector, url) {
  const element = document.querySelector(selector);
  if (!element) return;

  try {
    const basePath = getBasePath();
    const fullUrl = basePath + url;
    const response = await fetch(fullUrl);
    if (!response.ok) throw new Error(`Failed to load ${fullUrl}`);
    const html = await response.text();
    element.innerHTML = html;
  } catch (error) {
    console.error(`Error loading partial (${url}):`, error);
    element.innerHTML = '';
  }
}

// Set active navigation link based on current page
function setActiveNavLink() {
  const basePath = getBasePath();
  let currentPath = window.location.pathname;

  // Remove base path if present
  if (basePath !== '/' && currentPath.startsWith(basePath)) {
    currentPath = currentPath.substring(basePath.length);
  }

  // Remove leading slash
  if (currentPath.startsWith('/')) {
    currentPath = currentPath.substring(1);
  }

  // Remove trailing slash
  currentPath = currentPath.replace(/\/$/, '');

  // Default to index.html if empty
  if (currentPath === '' || currentPath === '/') {
    currentPath = 'index.html';
  }

  const navLinks = document.querySelectorAll('.nav-links a[data-nav]');

  navLinks.forEach(link => {
    let linkPath = link.getAttribute('href').replace(/\/$/, '');

    // Remove leading slash from link path
    if (linkPath.startsWith('/')) {
      linkPath = linkPath.substring(1);
    }

    // Check if current page matches link
    if (currentPath === linkPath || currentPath.endsWith('/' + linkPath)) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }
  });
}

// Smooth scroll for anchor links
function setupSmoothScroll() {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      const targetId = this.getAttribute('href').substring(1);
      if (!targetId) return;

      const targetElement = document.getElementById(targetId);
      if (targetElement) {
        e.preventDefault();
        targetElement.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
}

// Initialize all functionality when DOM is ready
async function init() {
  // Load header and footer
  await Promise.all([
    injectPartial('header[data-partial="header"]', 'partials/header.html'),
    injectPartial('footer[data-partial="footer"]', 'partials/footer.html')
  ]);

  // Set active nav link after header is loaded
  setActiveNavLink();

  // Update header menu based on auth state
  updateHeaderAuthState();

  // Setup smooth scrolling
  setupSmoothScroll();

  // Add fade-in animation to page
  document.body.style.opacity = '0';
  setTimeout(() => {
    document.body.style.transition = 'opacity 0.3s ease';
    document.body.style.opacity = '1';
  }, 50);
}

// Update header authentication state from localStorage
function updateHeaderAuthState() {
  const loginButtonContainer = document.getElementById('login-button-container');
  const accountButtonContainer = document.getElementById('account-button-container');
  const adminButtonContainer = document.getElementById('admin-button-container');
  const logoutButtonContainer = document.getElementById('logout-button-container');

  // Check localStorage directly
  const token = localStorage.getItem('auth_token');
  const userStr = localStorage.getItem('current_user');

  console.log('[App.js] Updating header auth state');
  console.log('[App.js] Token exists:', !!token);
  console.log('[App.js] User data exists:', !!userStr);

  if (!loginButtonContainer || !accountButtonContainer || !adminButtonContainer || !logoutButtonContainer) {
    console.warn('[App.js] Header elements not found');
    return;
  }

  if (token && userStr) {
    try {
      const user = JSON.parse(userStr);
      console.log('[App.js] User is logged in:', user.email, 'Role:', user.role);

      // Hide login button, show account and logout buttons
      loginButtonContainer.style.display = 'none';
      accountButtonContainer.style.display = 'block';
      logoutButtonContainer.style.display = 'block';

      // Show admin panel button for admins/moderators
      if (user.role === 'admin' || user.role === 'moderator') {
        console.log('[App.js] Showing admin panel button');
        adminButtonContainer.style.display = 'block';
      } else {
        adminButtonContainer.style.display = 'none';
      }
    } catch (error) {
      console.error('[App.js] Error parsing user data:', error);
      // If data is corrupted, show login
      loginButtonContainer.style.display = 'block';
      accountButtonContainer.style.display = 'none';
      adminButtonContainer.style.display = 'none';
      logoutButtonContainer.style.display = 'none';
    }
  } else {
    console.log('[App.js] User not logged in');
    // Show login button, hide account, admin and logout buttons
    loginButtonContainer.style.display = 'block';
    accountButtonContainer.style.display = 'none';
    adminButtonContainer.style.display = 'none';
    logoutButtonContainer.style.display = 'none';
  }
}

// Make function globally available for other scripts to call
window.updateHeaderAuthState = updateHeaderAuthState;

// Run initialization when DOM is fully loaded
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}

// Will need to add more functions for when database is connected but for now this generates the calender for the webpage.
const monthNames = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December'
];

const calendarBody = document.getElementById('calendar-body');
const monthYearLabel = document.getElementById('month-year');
const prevBtn = document.getElementById('prev-btn');
const nextBtn = document.getElementById('next-btn');

let currentDate = new Date();

function renderCalendar() {
  const currentMonth = currentDate.getMonth();
  const currentYear = currentDate.getFullYear();

  monthYearLabel.textContent = `${monthNames[currentMonth]} ${currentYear}`;

  const firstDay = new Date(currentYear, currentMonth, 1).getDay();
  const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

  calendarBody.innerHTML = '';

  let date = 1;

  for (let i = 0; i < 6; i++) {
    const row = document.createElement('tr');
    for (let j = 0; j < 7; j++) {
      if (i == 0 && j < firstDay) {
        const cell = document.createElement('td');
        row.appendChild(cell);
      }
      else if (date > daysInMonth) {
        break;
      } else {
        const cell = document.createElement('td');
        cell.textContent = date;
        row.appendChild(cell);
        date++;
      }
    }
    calendarBody.appendChild(row);
  }
}

prevBtn.addEventListener('click', () => {
  currentDate.setMonth(currentDate.getMonth() - 1);
  renderCalendar();
})

nextBtn.addEventListener('click', () => {
  currentDate.setMonth(currentDate.getMonth() + 1);
  renderCalendar();
})

renderCalendar();