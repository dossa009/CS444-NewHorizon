/**
 * NEW HORIZON - Main JavaScript
 * Handles navigation, partial injection, and page interactions
 */

// Inject HTML partials (header, footer)
async function injectPartial(selector, url) {
  const element = document.querySelector(selector);
  if (!element) return;

  try {
    const response = await fetch(url);
    if (!response.ok) throw new Error(`Failed to load ${url}`);
    const html = await response.text();
    element.innerHTML = html;
  } catch (error) {
    console.error(`Error loading partial (${url}):`, error);
    element.innerHTML = '';
  }
}

// Set active navigation link based on current page
function setActiveNavLink() {
  const currentPath = window.location.pathname.replace(/\/$/, '');
  const navLinks = document.querySelectorAll('.nav-links a[data-nav]');

  navLinks.forEach(link => {
    const linkPath = link.getAttribute('href').replace(/\/$/, '');

    // Check if current page matches link
    if (
      currentPath === linkPath ||
      (currentPath === '' && linkPath === '/index.html') ||
      (currentPath.endsWith('/index.html') && linkPath.endsWith('/index.html'))
    ) {
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
    injectPartial('header[data-partial="header"]', '/partials/header.html'),
    injectPartial('footer[data-partial="footer"]', '/partials/footer.html')
  ]);

  // Set active nav link after header is loaded
  setActiveNavLink();

  // Setup smooth scrolling
  setupSmoothScroll();

  // Add fade-in animation to page
  document.body.style.opacity = '0';
  setTimeout(() => {
    document.body.style.transition = 'opacity 0.3s ease';
    document.body.style.opacity = '1';
  }, 50);
}

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