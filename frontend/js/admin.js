/**
 * NEW HORIZON - Admin Dashboard
 */

let currentSection = 'dashboard';

function initAdminDashboard() {
  loadDashboardStats();
  setupNavigation();
}

function setupNavigation() {
  document.querySelectorAll('.admin-nav button').forEach(btn => {
    btn.addEventListener('click', () => {
      const section = btn.dataset.section;
      switchSection(section);
    });
  });
}

function switchSection(section) {
  currentSection = section;

  // Update nav
  document.querySelectorAll('.admin-nav button').forEach(b => b.classList.remove('active'));
  document.querySelector(`[data-section="${section}"]`).classList.add('active');

  // Update sections
  document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
  document.getElementById(`section-${section}`).classList.add('active');

  // Update title
  const titles = {
    dashboard: 'Dashboard',
    users: 'User Management',
    resources: 'Resources',
    forum: 'Forum Posts',
    events: 'Calendar Events',
    exercises: 'Exercises',
    opportunities: 'Opportunities',
    contact: 'Contact Messages'
  };
  document.getElementById('section-title').textContent = titles[section] || section;

  // Load data
  loadSectionData(section);
}

async function loadSectionData(section) {
  try {
    switch(section) {
      case 'dashboard': loadDashboardStats(); break;
      case 'users': loadUsers(); break;
      case 'resources': loadResources(); break;
      case 'forum': loadForumPosts(); break;
      case 'events': loadEvents(); break;
      case 'exercises': loadExercises(); break;
      case 'opportunities': loadOpportunities(); break;
      case 'contact': loadContacts(); break;
    }
  } catch (e) {
    console.error('Error loading section:', e);
  }
}

async function loadDashboardStats() {
  try {
    const stats = await API.admin.getDashboardStats();
    const grid = document.getElementById('stats-grid');
    if (grid && stats) {
      const cards = grid.querySelectorAll('.stat-value');
      if (cards[0]) cards[0].textContent = stats.users || 0;
      if (cards[1]) cards[1].textContent = stats.resources || 0;
      if (cards[2]) cards[2].textContent = stats.forum_posts || 0;
      if (cards[3]) cards[3].textContent = stats.events || 0;
    }
  } catch (e) { console.error(e); }
}

async function loadUsers() {
  const container = document.getElementById('users-list');
  container.innerHTML = '<p>Loading...</p>';
  try {
    const data = await API.admin.getUsers();
    const users = data.users || data || [];
    if (!users.length) {
      container.innerHTML = '<p>No users found.</p>';
      return;
    }
    container.innerHTML = `<table class="admin-table">
      <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
      <tbody>${users.map(u => `<tr>
        <td>${u.id}</td>
        <td>${u.first_name} ${u.last_name}</td>
        <td>${u.email}</td>
        <td><select onchange="updateUserRole(${u.id}, this.value)">
          <option value="user" ${u.role === 'user' ? 'selected' : ''}>User</option>
          <option value="moderator" ${u.role === 'moderator' ? 'selected' : ''}>Moderator</option>
          <option value="admin" ${u.role === 'admin' ? 'selected' : ''}>Admin</option>
        </select></td>
        <td><button class="btn-danger" onclick="deleteUser(${u.id})">Delete</button></td>
      </tr>`).join('')}</tbody>
    </table>`;
  } catch (e) { container.innerHTML = '<p>Error loading users.</p>'; }
}

async function loadResources() {
  const container = document.getElementById('resources-list');
  container.innerHTML = '<p>Loading...</p>';
  try {
    const data = await API.resources.getAll();
    const items = data.resources || data || [];
    if (!items.length) { container.innerHTML = '<p>No resources.</p>'; return; }
    container.innerHTML = items.map(r => `<div class="admin-item">
      <h4>${r.title}</h4>
      <p>${r.description || ''}</p>
      <button onclick="editResource(${r.id})">Edit</button>
      <button class="btn-danger" onclick="deleteResource(${r.id})">Delete</button>
    </div>`).join('');
  } catch (e) { container.innerHTML = '<p>Error loading.</p>'; }
}

async function loadForumPosts() {
  const container = document.getElementById('forum-list');
  container.innerHTML = '<p>Loading...</p>';
  try {
    const data = await API.forum.getPosts();
    const items = data.posts || data || [];
    if (!items.length) { container.innerHTML = '<p>No posts.</p>'; return; }
    container.innerHTML = items.map(p => `<div class="admin-item">
      <h4>${p.title}</h4>
      <p>${p.content ? p.content.substring(0, 100) + '...' : ''}</p>
      <button class="btn-danger" onclick="deletePost(${p.id})">Delete</button>
    </div>`).join('');
  } catch (e) { container.innerHTML = '<p>Error loading.</p>'; }
}

async function loadEvents() {
  const container = document.getElementById('events-list');
  container.innerHTML = '<p>Loading...</p>';
  try {
    const data = await API.calendar.getEvents();
    const items = data.events || data || [];
    if (!items.length) { container.innerHTML = '<p>No events.</p>'; return; }
    container.innerHTML = items.map(e => `<div class="admin-item">
      <h4>${e.title}</h4>
      <p>${e.event_date || e.date || ''}</p>
      <button onclick="editEvent(${e.id})">Edit</button>
      <button class="btn-danger" onclick="deleteEvent(${e.id})">Delete</button>
    </div>`).join('');
  } catch (e) { container.innerHTML = '<p>Error loading.</p>'; }
}

async function loadExercises() {
  const container = document.getElementById('exercises-list');
  container.innerHTML = '<p>Loading...</p>';
  try {
    const data = await API.exercises.getAll();
    const items = data.exercises || data || [];
    if (!items.length) { container.innerHTML = '<p>No exercises.</p>'; return; }
    container.innerHTML = items.map(e => `<div class="admin-item">
      <h4>${e.title}</h4>
      <p>${e.type} - ${e.difficulty}</p>
      <button onclick="editExercise(${e.id})">Edit</button>
      <button class="btn-danger" onclick="deleteExercise(${e.id})">Delete</button>
    </div>`).join('');
  } catch (e) { container.innerHTML = '<p>Error loading.</p>'; }
}

async function loadOpportunities() {
  const container = document.getElementById('opportunities-list');
  container.innerHTML = '<p>Loading...</p>';
  try {
    const data = await API.opportunities.getAll();
    const items = data.opportunities || data || [];
    if (!items.length) { container.innerHTML = '<p>No opportunities.</p>'; return; }
    container.innerHTML = items.map(o => `<div class="admin-item">
      <h4>${o.title}</h4>
      <p>${o.organization || ''} - ${o.type}</p>
      <button onclick="editOpportunity(${o.id})">Edit</button>
      <button class="btn-danger" onclick="deleteOpportunity(${o.id})">Delete</button>
    </div>`).join('');
  } catch (e) { container.innerHTML = '<p>Error loading.</p>'; }
}

async function loadContacts() {
  const container = document.getElementById('contacts-list');
  container.innerHTML = '<p>Loading...</p>';
  try {
    const data = await API.contact.getMessages();
    const items = data.messages || data || [];
    if (!items.length) { container.innerHTML = '<p>No messages.</p>'; return; }
    container.innerHTML = items.map(c => `<div class="admin-item">
      <h4>${c.name} - ${c.email}</h4>
      <p>${c.message}</p>
    </div>`).join('');
  } catch (e) { container.innerHTML = '<p>Error loading.</p>'; }
}

// Modal functions
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openResourceModal() { document.getElementById('resource-modal-title').textContent = 'Add New Resource'; document.getElementById('form-resource').reset(); document.getElementById('resource-id').value = ''; openModal('modal-resource'); }
function openEventModal() { document.getElementById('event-modal-title').textContent = 'Add New Event'; document.getElementById('form-event').reset(); document.getElementById('event-id').value = ''; openModal('modal-event'); }
function openExerciseModal() { document.getElementById('exercise-modal-title').textContent = 'Add New Exercise'; document.getElementById('form-exercise').reset(); document.getElementById('exercise-id').value = ''; openModal('modal-exercise'); }
function openOpportunityModal() { document.getElementById('opportunity-modal-title').textContent = 'Add New Opportunity'; document.getElementById('form-opportunity').reset(); document.getElementById('opportunity-id').value = ''; openModal('modal-opportunity'); }

// CRUD operations
async function updateUserRole(id, role) { try { await API.admin.updateUser(id, { role }); showSuccess('Role updated'); } catch (e) { showError(e.message); } }
async function deleteUser(id) { if (confirm('Delete user?')) { try { await API.admin.deleteUser(id); loadUsers(); showSuccess('Deleted'); } catch (e) { showError(e.message); } } }
async function deleteResource(id) { if (confirm('Delete?')) { try { await API.resources.delete(id); loadResources(); } catch (e) { showError(e.message); } } }
async function deletePost(id) { if (confirm('Delete?')) { try { await API.forum.deletePost(id); loadForumPosts(); } catch (e) { showError(e.message); } } }
async function deleteEvent(id) { if (confirm('Delete?')) { try { await API.calendar.deleteEvent(id); loadEvents(); } catch (e) { showError(e.message); } } }
async function deleteExercise(id) { if (confirm('Delete?')) { try { await API.exercises.delete(id); loadExercises(); } catch (e) { showError(e.message); } } }
async function deleteOpportunity(id) { if (confirm('Delete?')) { try { await API.opportunities.delete(id); loadOpportunities(); } catch (e) { showError(e.message); } } }

// Forms
document.getElementById('form-resource')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = {
    title: document.getElementById('resource-title').value,
    description: document.getElementById('resource-description').value,
    content: document.getElementById('resource-content').value,
    category: document.getElementById('resource-category').value,
    tags: document.getElementById('resource-tags').value,
    external_url: document.getElementById('resource-url').value,
    is_published: document.getElementById('resource-published').checked
  };
  const id = document.getElementById('resource-id').value;
  try {
    if (id) await API.resources.update(id, data);
    else await API.resources.create(data);
    closeModal('modal-resource');
    loadResources();
    showSuccess('Saved');
  } catch (e) { showError(e.message); }
});

document.getElementById('form-event')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = {
    title: document.getElementById('event-title').value,
    description: document.getElementById('event-description').value,
    event_date: document.getElementById('event-date').value,
    event_type: document.getElementById('event-type').value,
    start_time: document.getElementById('event-start-time').value,
    end_time: document.getElementById('event-end-time').value,
    location: document.getElementById('event-location').value,
    max_participants: document.getElementById('event-max-participants').value || null,
    is_online: document.getElementById('event-online').checked,
    is_published: document.getElementById('event-published').checked
  };
  const id = document.getElementById('event-id').value;
  try {
    if (id) await API.calendar.updateEvent(id, data);
    else await API.calendar.createEvent(data);
    closeModal('modal-event');
    loadEvents();
    showSuccess('Saved');
  } catch (e) { showError(e.message); }
});

document.getElementById('form-exercise')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = {
    title: document.getElementById('exercise-title').value,
    description: document.getElementById('exercise-description').value,
    instructions: document.getElementById('exercise-instructions').value,
    type: document.getElementById('exercise-type').value,
    difficulty: document.getElementById('exercise-difficulty').value,
    duration: document.getElementById('exercise-duration').value
  };
  const id = document.getElementById('exercise-id').value;
  try {
    if (id) await API.exercises.update(id, data);
    else await API.exercises.create(data);
    closeModal('modal-exercise');
    loadExercises();
    showSuccess('Saved');
  } catch (e) { showError(e.message); }
});

document.getElementById('form-opportunity')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const data = {
    title: document.getElementById('opportunity-title').value,
    description: document.getElementById('opportunity-description').value,
    type: document.getElementById('opportunity-type').value,
    organization: document.getElementById('opportunity-organization').value,
    location: document.getElementById('opportunity-location').value,
    contact_email: document.getElementById('opportunity-contact-email').value,
    apply_url: document.getElementById('opportunity-apply-url').value,
    is_remote: document.getElementById('opportunity-remote').checked,
    is_published: document.getElementById('opportunity-published').checked
  };
  const id = document.getElementById('opportunity-id').value;
  try {
    if (id) await API.opportunities.update(id, data);
    else await API.opportunities.create(data);
    closeModal('modal-opportunity');
    loadOpportunities();
    showSuccess('Saved');
  } catch (e) { showError(e.message); }
});

function showSuccess(msg) { const el = document.getElementById('success-alert'); el.textContent = msg; el.style.display = 'block'; setTimeout(() => el.style.display = 'none', 3000); }
function showError(msg) { const el = document.getElementById('error-alert'); el.textContent = msg; el.style.display = 'block'; setTimeout(() => el.style.display = 'none', 3000); }
