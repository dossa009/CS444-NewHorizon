let currentSection = 'dashboard';

function initAdminDashboard() {
  loadDashboardStats();
  setupNavigation();
  setupForms();
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

  document.querySelectorAll('.admin-nav button').forEach(b => b.classList.remove('active'));
  document.querySelector(`[data-section="${section}"]`).classList.add('active');

  document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
  document.getElementById(`section-${section}`).classList.add('active');

  const titles = {
    dashboard: 'Dashboard',
    users: 'User Management',
    resources: 'Resources',
    exercises: 'Exercises',
    events: 'Calendar Events',
    forum: 'Forum Posts'
  };
  document.getElementById('section-title').textContent = titles[section] || section;

  loadSectionData(section);
}

function loadSectionData(section) {
  switch(section) {
    case 'dashboard': loadDashboardStats(); break;
    case 'users': loadUsers(); break;
    case 'resources': loadResources(); break;
    case 'exercises': loadExercises(); break;
    case 'events': loadEvents(); break;
    case 'forum': loadForumPosts(); break;
  }
}

async function loadDashboardStats() {
  try {
    const data = await API.admin.getDashboardStats();
    const grid = document.getElementById('stats-grid');
    if (grid && data && data.stats) {
      const cards = grid.querySelectorAll('.stat-value');
      if (cards[0]) cards[0].textContent = data.stats.users?.total_users || 0;
      if (cards[1]) cards[1].textContent = data.stats.resources?.total_resources || 0;
      if (cards[2]) cards[2].textContent = data.stats.exercises?.total_exercises || 0;
      if (cards[3]) cards[3].textContent = data.stats.discussion?.total_posts || 0;
      if (cards[4]) cards[4].textContent = data.stats.events?.total_events || 0;
    }
  } catch (e) { console.error(e); }
}

async function loadUsers() {
  const container = document.getElementById('users-list');
  container.innerHTML = '<p>Loading...</p>';
  try {
    const data = await API.admin.getUsers();
    const users = data.users || [];
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
    const items = data.resources || [];
    if (!items.length) { container.innerHTML = '<p>No resources.</p>'; return; }
    container.innerHTML = items.map(r => `<div class="admin-item">
      <h4>${r.title}</h4>
      <p>${r.description || 'No description'}</p>
      ${r.resource_url ? `<p><a href="${r.resource_url}" target="_blank">${r.resource_url}</a></p>` : ''}
      <button onclick="editResource(${r.id})">Edit</button>
      <button class="btn-danger" onclick="deleteResource(${r.id})">Delete</button>
    </div>`).join('');
  } catch (e) { container.innerHTML = '<p>Error loading.</p>'; }
}

async function loadExercises() {
  const container = document.getElementById('exercises-list');
  container.innerHTML = '<p>Loading...</p>';
  try {
    const data = await API.exercises.getAll();
    const items = data.exercises || [];
    if (!items.length) { container.innerHTML = '<p>No exercises.</p>'; return; }
    container.innerHTML = items.map(e => `<div class="admin-item">
      <h4>${e.name}</h4>
      <p>${e.description || 'No description'}</p>
      ${e.exercise_url ? `<p><a href="${e.exercise_url}" target="_blank">${e.exercise_url}</a></p>` : ''}
      <button onclick="editExercise(${e.id})">Edit</button>
      <button class="btn-danger" onclick="deleteExercise(${e.id})">Delete</button>
    </div>`).join('');
  } catch (e) { container.innerHTML = '<p>Error loading.</p>'; }
}

async function loadEvents() {
  const container = document.getElementById('events-list');
  container.innerHTML = '<p>Loading...</p>';
  try {
    const data = await API.calendar.getEvents();
    const items = data.events || [];
    if (!items.length) { container.innerHTML = '<p>No events.</p>'; return; }
    container.innerHTML = items.map(e => `<div class="admin-item">
      <h4>Event #${e.id}</h4>
      <p>Date: ${e.date || 'No date'}</p>
      ${e.url ? `<p><a href="${e.url}" target="_blank">${e.url}</a></p>` : ''}
      <p>Created by: ${e.user_name || 'Unknown'}</p>
      <button onclick="editEvent(${e.id})">Edit</button>
      <button class="btn-danger" onclick="deleteEvent(${e.id})">Delete</button>
    </div>`).join('');
  } catch (e) { container.innerHTML = '<p>Error loading.</p>'; }
}

async function loadForumPosts() {
  const container = document.getElementById('forum-list');
  container.innerHTML = '<p>Loading...</p>';
  try {
    const data = await API.forum.getPosts();
    const items = data.posts || [];
    if (!items.length) { container.innerHTML = '<p>No posts.</p>'; return; }
    container.innerHTML = items.map(p => `<div class="admin-item">
      <h4>${p.title}</h4>
      <p>By: ${p.author || 'Anonymous'} - ${p.created_at || ''}</p>
      <button onclick="editPost(${p.id})">Edit</button>
      <button class="btn-danger" onclick="deletePost(${p.id})">Delete</button>
    </div>`).join('');
  } catch (e) { container.innerHTML = '<p>Error loading.</p>'; }
}

// Modal functions
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

function openResourceModal() {
  document.getElementById('resource-modal-title').textContent = 'Add New Resource';
  document.getElementById('form-resource').reset();
  document.getElementById('resource-id').value = '';
  openModal('modal-resource');
}

function openExerciseModal() {
  document.getElementById('exercise-modal-title').textContent = 'Add New Exercise';
  document.getElementById('form-exercise').reset();
  document.getElementById('exercise-id').value = '';
  openModal('modal-exercise');
}

function openEventModal() {
  document.getElementById('event-modal-title').textContent = 'Add New Event';
  document.getElementById('form-event').reset();
  document.getElementById('event-id').value = '';
  openModal('modal-event');
}

function openForumModal() {
  document.getElementById('forum-modal-title').textContent = 'Add New Post';
  document.getElementById('form-forum').reset();
  document.getElementById('forum-id').value = '';
  openModal('modal-forum');
}

// Edit functions
async function editResource(id) {
  try {
    const data = await API.resources.getById(id);
    const r = data.resource;
    document.getElementById('resource-id').value = r.id;
    document.getElementById('resource-title').value = r.title || '';
    document.getElementById('resource-description').value = r.description || '';
    document.getElementById('resource-url').value = r.resource_url || '';
    document.getElementById('resource-modal-title').textContent = 'Edit Resource';
    openModal('modal-resource');
  } catch (e) { showError('Failed to load resource'); }
}

async function editExercise(id) {
  try {
    const data = await API.exercises.getById(id);
    const e = data.exercise;
    document.getElementById('exercise-id').value = e.id;
    document.getElementById('exercise-name').value = e.name || '';
    document.getElementById('exercise-description').value = e.description || '';
    document.getElementById('exercise-url').value = e.exercise_url || '';
    document.getElementById('exercise-modal-title').textContent = 'Edit Exercise';
    openModal('modal-exercise');
  } catch (e) { showError('Failed to load exercise'); }
}

async function editEvent(id) {
  try {
    const data = await API.calendar.getEvent(id);
    const e = data.event;
    document.getElementById('event-id').value = e.id;
    document.getElementById('event-url').value = e.url || '';
    document.getElementById('event-date').value = e.date || '';
    document.getElementById('event-modal-title').textContent = 'Edit Event';
    openModal('modal-event');
  } catch (e) { showError('Failed to load event'); }
}

async function editPost(id) {
  try {
    const data = await API.forum.getPost(id);
    const p = data.post;
    document.getElementById('forum-id').value = p.id;
    document.getElementById('forum-title').value = p.title || '';
    document.getElementById('forum-modal-title').textContent = 'Edit Post';
    openModal('modal-forum');
  } catch (e) { showError('Failed to load post'); }
}

// CRUD operations
async function updateUserRole(id, role) {
  try {
    await API.admin.updateUser(id, { role });
    showSuccess('Role updated');
  } catch (e) { showError(e.message); }
}

async function deleteUser(id) {
  if (confirm('Delete this user?')) {
    try {
      await API.admin.deleteUser(id);
      loadUsers();
      showSuccess('User deleted');
    } catch (e) { showError(e.message); }
  }
}

async function deleteResource(id) {
  if (confirm('Delete this resource?')) {
    try {
      await API.resources.delete(id);
      loadResources();
      showSuccess('Resource deleted');
    } catch (e) { showError(e.message); }
  }
}

async function deleteExercise(id) {
  if (confirm('Delete this exercise?')) {
    try {
      await API.exercises.delete(id);
      loadExercises();
      showSuccess('Exercise deleted');
    } catch (e) { showError(e.message); }
  }
}

async function deleteEvent(id) {
  if (confirm('Delete this event?')) {
    try {
      await API.calendar.deleteEvent(id);
      loadEvents();
      showSuccess('Event deleted');
    } catch (e) { showError(e.message); }
  }
}

async function deletePost(id) {
  if (confirm('Delete this post?')) {
    try {
      await API.forum.deletePost(id);
      loadForumPosts();
      showSuccess('Post deleted');
    } catch (e) { showError(e.message); }
  }
}

// Form submissions
function setupForms() {
  document.getElementById('form-resource')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
      title: document.getElementById('resource-title').value,
      description: document.getElementById('resource-description').value,
      resource_url: document.getElementById('resource-url').value
    };
    const id = document.getElementById('resource-id').value;
    try {
      if (id) await API.resources.update(id, data);
      else await API.resources.create(data);
      closeModal('modal-resource');
      loadResources();
      showSuccess('Resource saved');
    } catch (e) { showError(e.message); }
  });

  document.getElementById('form-exercise')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
      name: document.getElementById('exercise-name').value,
      description: document.getElementById('exercise-description').value,
      exercise_url: document.getElementById('exercise-url').value
    };
    const id = document.getElementById('exercise-id').value;
    try {
      if (id) await API.exercises.update(id, data);
      else await API.exercises.create(data);
      closeModal('modal-exercise');
      loadExercises();
      showSuccess('Exercise saved');
    } catch (e) { showError(e.message); }
  });

  document.getElementById('form-event')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
      url: document.getElementById('event-url').value,
      date: document.getElementById('event-date').value
    };
    const id = document.getElementById('event-id').value;
    try {
      if (id) await API.calendar.updateEvent(id, data);
      else await API.calendar.createEvent(data);
      closeModal('modal-event');
      loadEvents();
      showSuccess('Event saved');
    } catch (e) { showError(e.message); }
  });

  document.getElementById('form-forum')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = {
      title: document.getElementById('forum-title').value
    };
    const id = document.getElementById('forum-id').value;
    try {
      if (id) await API.forum.updatePost(id, data);
      else await API.forum.createPost(data);
      closeModal('modal-forum');
      loadForumPosts();
      showSuccess('Post saved');
    } catch (e) { showError(e.message); }
  });
}

function showSuccess(msg) {
  const el = document.getElementById('success-alert');
  el.textContent = msg;
  el.style.display = 'block';
  setTimeout(() => el.style.display = 'none', 3000);
}

function showError(msg) {
  const el = document.getElementById('error-alert');
  el.textContent = msg;
  el.style.display = 'block';
  setTimeout(() => el.style.display = 'none', 3000);
}
