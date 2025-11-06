/**
 * Admin Dashboard JavaScript
 * Handles all admin panel functionality
 */

// Global state
let currentEditingId = null;
let currentSection = 'dashboard';

// Initialize dashboard
function initAdminDashboard() {
  loadDashboardStats();
  setupNavigation();
}

// ========== Navigation ==========
function setupNavigation() {
  document.querySelectorAll('[data-section]').forEach(btn => {
    btn.addEventListener('click', () => {
      const section = btn.dataset.section;
      switchSection(section);
    });
  });
}

function switchSection(sectionName) {
  currentSection = sectionName;

  // Update buttons
  document.querySelectorAll('[data-section]').forEach(b => b.classList.remove('active'));
  document.querySelector(`[data-section="${sectionName}"]`).classList.add('active');

  // Update sections
  document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
  document.getElementById(`section-${sectionName}`).classList.add('active');

  // Update title
  const titles = {
    dashboard: 'Dashboard',
    users: 'Users Management',
    resources: 'Resources Management',
    forum: 'Forum Moderation',
    calendar: 'Events Management',
    exercises: 'Exercises Management',
    opportunities: 'Opportunities Management',
    contacts: 'Contact Messages'
  };
  document.getElementById('section-title').textContent = titles[sectionName];

  // Load data for section
  loadSectionData(sectionName);
}

function loadSectionData(section) {
  switch(section) {
    case 'dashboard':
      loadDashboardStats();
      break;
    case 'users':
      loadUsers();
      break;
    case 'resources':
      loadResources();
      break;
    case 'forum':
      loadForumPosts();
      break;
    case 'calendar':
      loadEvents();
      break;
    case 'exercises':
      loadExercises();
      break;
    case 'opportunities':
      loadOpportunities();
      break;
    case 'contacts':
      loadContactMessages();
      break;
  }
}

// ========== Dashboard Stats ==========
async function loadDashboardStats() {
  try {
    const { stats } = await API.admin.getDashboardStats();

    const html = `
      <div class="stat-card">
        <h3>Total Users</h3>
        <p class="stat-value">${stats.users.total_users}</p>
        <p class="stat-label">+${stats.users.new_users_month} this month</p>
      </div>
      <div class="stat-card">
        <h3>Resources</h3>
        <p class="stat-value">${stats.resources.published_resources}</p>
        <p class="stat-label">of ${stats.resources.total_resources} total</p>
      </div>
      <div class="stat-card">
        <h3>Forum Posts</h3>
        <p class="stat-value">${stats.forum.total_posts}</p>
        <p class="stat-label">${stats.forum.pending_posts || 0} pending</p>
      </div>
      <div class="stat-card">
        <h3>Upcoming Events</h3>
        <p class="stat-value">${stats.events.upcoming_events}</p>
        <p class="stat-label">of ${stats.events.total_events} total</p>
      </div>
      <div class="stat-card">
        <h3>Contact Messages</h3>
        <p class="stat-value">${stats.messages.unread_messages || 0}</p>
        <p class="stat-label">of ${stats.messages.total_messages} total</p>
      </div>
    `;

    document.getElementById('stats-grid').innerHTML = html;
  } catch (error) {
    showError('Failed to load dashboard stats');
    console.error(error);
  }
}

// ========== Users Management ==========
async function loadUsers() {
  const container = document.getElementById('users-list');
  container.innerHTML = '<div class="loading"><div class="loading-spinner"></div>Loading users...</div>';

  try {
    const { users } = await API.admin.getUsers();

    if (users.length === 0) {
      container.innerHTML = '<div class="empty-state"><h3>No users found</h3></div>';
      return;
    }

    const html = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Email</th>
            <th>Name</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${users.map(user => `
            <tr>
              <td>${user.email}</td>
              <td>${user.first_name} ${user.last_name}</td>
              <td><span class="badge badge-info">${user.role}</span></td>
              <td>${user.is_active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-danger">Inactive</span>'}</td>
              <td>${new Date(user.created_at).toLocaleDateString()}</td>
              <td class="actions">
                <button class="btn btn-sm btn-secondary" onclick="changeUserRole(${user.id}, '${user.role}')">Change Role</button>
                ${user.id !== API.auth.getCurrentUser().id ? `<button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">Delete</button>` : ''}
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;

    container.innerHTML = html;
  } catch (error) {
    container.innerHTML = '<div class="empty-state"><h3>Failed to load users</h3></div>';
    showError('Failed to load users');
  }
}

async function changeUserRole(userId, currentRole) {
  const roles = ['user', 'moderator', 'admin'];
  const newRole = prompt(`Change user role to (current: ${currentRole}):\n- user\n- moderator\n- admin`, currentRole);

  if (newRole && roles.includes(newRole) && newRole !== currentRole) {
    try {
      await API.admin.updateUser(userId, { role: newRole });
      showSuccess('User role updated successfully');
      loadUsers();
    } catch (error) {
      showError('Failed to update user role');
    }
  }
}

async function deleteUser(userId) {
  if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
    try {
      await API.admin.deleteUser(userId);
      showSuccess('User deleted successfully');
      loadUsers();
    } catch (error) {
      showError('Failed to delete user');
    }
  }
}

// ========== Resources Management ==========
async function loadResources() {
  const container = document.getElementById('resources-list');
  container.innerHTML = '<div class="loading"><div class="loading-spinner"></div>Loading resources...</div>';

  try {
    const { resources } = await API.admin.getResources();

    if (resources.length === 0) {
      container.innerHTML = '<div class="empty-state"><h3>No resources found</h3><p>Create your first resource to get started</p></div>';
      return;
    }

    const html = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Views</th>
            <th>Created</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${resources.map(resource => `
            <tr>
              <td><strong>${resource.title}</strong></td>
              <td><span class="badge badge-info">${resource.category}</span></td>
              <td>${resource.views_count || 0}</td>
              <td>${new Date(resource.created_at).toLocaleDateString()}</td>
              <td>
                <button class="btn btn-sm btn-${resource.is_published ? 'success' : 'warning'}" onclick="toggleResourceStatus(${resource.id}, ${resource.is_published})">
                  ${resource.is_published ? '✓ Published' : '○ Draft'}
                </button>
              </td>
              <td class="actions">
                <button class="btn btn-sm btn-secondary" onclick="editResource(${resource.id})">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteResource(${resource.id})">Delete</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;

    container.innerHTML = html;
  } catch (error) {
    container.innerHTML = '<div class="empty-state"><h3>Failed to load resources</h3></div>';
    showError('Failed to load resources');
  }
}

async function toggleResourceStatus(id, currentStatus) {
  try {
    await API.admin.updateResource(id, { is_published: !currentStatus });
    showSuccess(`Resource ${currentStatus ? 'unpublished' : 'published'} successfully`);
    setTimeout(() => loadResources(), 500);
  } catch (error) {
    showError('Failed to update resource status');
  }
}

async function deleteResource(id) {
  if (confirm('Are you sure you want to delete this resource?')) {
    try {
      await API.resources.delete(id);
      showSuccess('Resource deleted successfully');
      loadResources();
    } catch (error) {
      showError('Failed to delete resource');
    }
  }
}

// ========== Forum Moderation ==========
async function loadForumPosts() {
  const container = document.getElementById('forum-list');
  container.innerHTML = '<div class="loading"><div class="loading-spinner"></div>Loading forum posts...</div>';

  try {
    const { posts } = await API.admin.getForumPosts();

    if (posts.length === 0) {
      container.innerHTML = '<div class="empty-state"><h3>No forum posts found</h3></div>';
      return;
    }

    const html = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Status</th>
            <th>Views</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${posts.map(post => `
            <tr>
              <td><strong>${post.title}</strong></td>
              <td>${post.author_name}</td>
              <td>
                ${post.status === 'approved' ? '<span class="badge badge-success">Approved</span>' :
                  post.status === 'pending' ? '<span class="badge badge-warning">Pending</span>' :
                  '<span class="badge badge-danger">Rejected</span>'}
              </td>
              <td>${post.views_count}</td>
              <td>${new Date(post.created_at).toLocaleDateString()}</td>
              <td class="actions">
                ${post.status === 'pending' ? `
                  <button class="btn btn-sm btn-success" onclick="moderatePost(${post.id}, 'approved')">Approve</button>
                  <button class="btn btn-sm btn-warning" onclick="moderatePost(${post.id}, 'rejected')">Reject</button>
                ` : `
                  <button class="btn btn-sm btn-secondary" onclick="viewPost(${post.id})">View</button>
                `}
                <button class="btn btn-sm btn-danger" onclick="deleteForumPost(${post.id})">Delete</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;

    container.innerHTML = html;
  } catch (error) {
    container.innerHTML = '<div class="empty-state"><h3>Failed to load forum posts</h3></div>';
    showError('Failed to load forum posts');
  }
}

async function moderatePost(postId, status) {
  try {
    await API.admin.moderatePost(postId, status);
    showSuccess(`Post ${status} successfully`);
    setTimeout(() => loadForumPosts(), 500);
  } catch (error) {
    showError('Failed to moderate post');
  }
}

async function deleteForumPost(postId) {
  if (confirm('Are you sure you want to delete this post?')) {
    try {
      await API.admin.deletePost(postId);
      showSuccess('Post deleted successfully');
      setTimeout(() => loadForumPosts(), 500);
    } catch (error) {
      showError('Failed to delete post');
    }
  }
}

function viewPost(postId) {
  // Open post in new tab or show in modal
  window.open(`pages/forum.html#post-${postId}`, '_blank');
}

// ========== Events Management ==========
async function loadEvents() {
  const container = document.getElementById('events-list');
  container.innerHTML = '<div class="loading"><div class="loading-spinner"></div>Loading events...</div>';

  try {
    const { events } = await API.admin.getEvents();

    if (events.length === 0) {
      container.innerHTML = '<div class="empty-state"><h3>No events found</h3><p>Create your first event to get started</p></div>';
      return;
    }

    const html = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Date</th>
            <th>Type</th>
            <th>Registered</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${events.map(event => `
            <tr>
              <td><strong>${event.title}</strong></td>
              <td>${new Date(event.event_date).toLocaleDateString()}</td>
              <td><span class="badge badge-info">${event.event_type}</span></td>
              <td>${event.registered_count || 0}${event.max_participants ? `/${event.max_participants}` : ''}</td>
              <td>
                <button class="btn btn-sm btn-${event.is_published ? 'success' : 'warning'}" onclick="toggleEventStatus(${event.id}, ${event.is_published})">
                  ${event.is_published ? '✓ Published' : '○ Draft'}
                </button>
              </td>
              <td class="actions">
                <button class="btn btn-sm btn-secondary" onclick="editEvent(${event.id})">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteEvent(${event.id})">Delete</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;

    container.innerHTML = html;
  } catch (error) {
    container.innerHTML = '<div class="empty-state"><h3>Failed to load events</h3></div>';
    showError('Failed to load events');
  }
}

async function toggleEventStatus(id, currentStatus) {
  try {
    await API.admin.updateEvent(id, { is_published: !currentStatus });
    showSuccess(`Event ${currentStatus ? 'unpublished' : 'published'} successfully`);
    setTimeout(() => loadEvents(), 500);
  } catch (error) {
    showError('Failed to update event status');
  }
}

// ========== Exercises Management ==========
async function loadExercises() {
  const container = document.getElementById('exercises-list');
  container.innerHTML = '<div class="loading"><div class="loading-spinner"></div>Loading exercises...</div>';

  try {
    const { exercises } = await API.admin.getExercises();

    if (exercises.length === 0) {
      container.innerHTML = '<div class="empty-state"><h3>No exercises found</h3><p>Create your first exercise to get started</p></div>';
      return;
    }

    const html = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Difficulty</th>
            <th>Duration</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${exercises.map(exercise => `
            <tr>
              <td><strong>${exercise.title}</strong></td>
              <td><span class="badge badge-info">${exercise.category}</span></td>
              <td><span class="badge badge-${exercise.difficulty === 'beginner' ? 'success' : exercise.difficulty === 'intermediate' ? 'warning' : 'danger'}">${exercise.difficulty}</span></td>
              <td>${exercise.duration_minutes} min</td>
              <td>
                <button class="btn btn-sm btn-${exercise.is_published ? 'success' : 'warning'}" onclick="toggleExerciseStatus(${exercise.id}, ${exercise.is_published})">
                  ${exercise.is_published ? '✓ Published' : '○ Draft'}
                </button>
              </td>
              <td class="actions">
                <button class="btn btn-sm btn-secondary" onclick="editExercise(${exercise.id})">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteExercise(${exercise.id})">Delete</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;

    container.innerHTML = html;
  } catch (error) {
    container.innerHTML = '<div class="empty-state"><h3>Failed to load exercises</h3></div>';
    showError('Failed to load exercises');
  }
}

async function toggleExerciseStatus(id, currentStatus) {
  try {
    await API.admin.updateExercise(id, { is_published: !currentStatus });
    showSuccess(`Exercise ${currentStatus ? 'unpublished' : 'published'} successfully`);
    setTimeout(() => loadExercises(), 500);
  } catch (error) {
    showError('Failed to update exercise status');
  }
}

// ========== Opportunities Management ==========
async function loadOpportunities() {
  const container = document.getElementById('opportunities-list');
  container.innerHTML = '<div class="loading"><div class="loading-spinner"></div>Loading opportunities...</div>';

  try {
    const { opportunities } = await API.admin.getOpportunities();

    if (opportunities.length === 0) {
      container.innerHTML = '<div class="empty-state"><h3>No opportunities found</h3><p>Create your first opportunity to get started</p></div>';
      return;
    }

    const html = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Type</th>
            <th>Organization</th>
            <th>Remote</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${opportunities.map(opp => `
            <tr>
              <td><strong>${opp.title}</strong></td>
              <td><span class="badge badge-info">${opp.opportunity_type}</span></td>
              <td>${opp.organization || '-'}</td>
              <td>${opp.is_remote ? 'Yes' : 'No'}</td>
              <td>
                <button class="btn btn-sm btn-${opp.is_published ? 'success' : 'warning'}" onclick="toggleOpportunityStatus(${opp.id}, ${opp.is_published})">
                  ${opp.is_published ? '✓ Published' : '○ Draft'}
                </button>
              </td>
              <td class="actions">
                <button class="btn btn-sm btn-secondary" onclick="editOpportunity(${opp.id})">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteOpportunity(${opp.id})">Delete</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;

    container.innerHTML = html;
  } catch (error) {
    container.innerHTML = '<div class="empty-state"><h3>Failed to load opportunities</h3></div>';
    showError('Failed to load opportunities');
  }
}

async function toggleOpportunityStatus(id, currentStatus) {
  try {
    await API.admin.updateOpportunity(id, { is_published: !currentStatus });
    showSuccess(`Opportunity ${currentStatus ? 'unpublished' : 'published'} successfully`);
    setTimeout(() => loadOpportunities(), 500);
  } catch (error) {
    showError('Failed to update opportunity status');
  }
}

// ========== Contact Messages ==========
async function loadContactMessages() {
  const container = document.getElementById('contacts-list');
  container.innerHTML = '<div class="loading"><div class="loading-spinner"></div>Loading messages...</div>';

  try {
    const { messages } = await API.admin.getContactMessages();

    if (messages.length === 0) {
      container.innerHTML = '<div class="empty-state"><h3>No contact messages found</h3></div>';
      return;
    }

    const html = `
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Subject</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          ${messages.map(msg => `
            <tr>
              <td>${msg.name}</td>
              <td>${msg.email}</td>
              <td>${msg.subject || '(No subject)'}</td>
              <td>
                <span class="badge badge-${msg.status === 'new' ? 'danger' : msg.status === 'read' ? 'warning' : msg.status === 'replied' ? 'success' : 'info'}">
                  ${msg.status}
                </span>
              </td>
              <td>${new Date(msg.created_at).toLocaleDateString()}</td>
              <td class="actions">
                <button class="btn btn-sm btn-secondary" onclick="viewContactMessage(${msg.id})">View</button>
                ${msg.status !== 'replied' ? `<button class="btn btn-sm btn-success" onclick="markAsReplied(${msg.id})">Mark Replied</button>` : ''}
                <button class="btn btn-sm btn-danger" onclick="deleteContactMessage(${msg.id})">Delete</button>
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    `;

    container.innerHTML = html;
  } catch (error) {
    container.innerHTML = '<div class="empty-state"><h3>Failed to load messages</h3></div>';
    showError('Failed to load contact messages');
  }
}

function viewContactMessage(id, message) {
  alert(`Message:\n\n${message}`);
}

async function markAsReplied(id) {
  try {
    await API.admin.updateContactMessage(id, { status: 'replied' });
    showSuccess('Message marked as replied');
    loadContactMessages();
  } catch (error) {
    showError('Failed to update message status');
  }
}

// ========== Alert Helpers ==========
function showSuccess(message) {
  const el = document.getElementById('success-alert');
  el.textContent = message;
  el.style.display = 'block';
  setTimeout(() => { el.style.display = 'none'; }, 5000);
}

function showError(message) {
  const el = document.getElementById('error-alert');
  el.textContent = message;
  el.style.display = 'block';
  setTimeout(() => { el.style.display = 'none'; }, 5000);
}

// ========== Modal Management ==========
function openModal(modalId) {
  document.getElementById(modalId).classList.add('show');
}

function closeModal(modalId) {
  document.getElementById(modalId).classList.remove('show');
  // Reset form
  const form = document.getElementById(modalId).querySelector('form');
  if (form) form.reset();
  // Clear hidden ID fields
  const hiddenId = form.querySelector('input[type="hidden"]');
  if (hiddenId) hiddenId.value = '';
}

// Close modal on background click
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal')) {
    closeModal(e.target.id);
  }
});

// ========== Resource Modal ==========
function openResourceModal(resourceId = null) {
  const modal = document.getElementById('modal-resource');
  const title = document.getElementById('resource-modal-title');

  if (resourceId) {
    title.textContent = 'Edit Resource';
    loadResourceData(resourceId);
  } else {
    title.textContent = 'Add New Resource';
    document.getElementById('form-resource').reset();
  }

  openModal('modal-resource');
}

async function loadResourceData(id) {
  try {
    const data = await API.admin.getResource(id);
    const resource = data.resource || data;
    document.getElementById('resource-id').value = resource.id;
    document.getElementById('resource-title').value = resource.title;
    document.getElementById('resource-description').value = resource.description;
    document.getElementById('resource-content').value = resource.content;
    document.getElementById('resource-category').value = resource.category;

    // Convert tags array to comma-separated string
    const tagsStr = resource.tags && Array.isArray(resource.tags)
      ? resource.tags.join(', ')
      : '';
    document.getElementById('resource-tags').value = tagsStr;

    document.getElementById('resource-url').value = resource.external_url || '';
    document.getElementById('resource-published').checked = resource.is_published;
  } catch (error) {
    alert('Error loading resource: ' + error.message);
  }
}

function editResource(id) {
  openResourceModal(id);
}

async function deleteEvent(id) {
  if (!confirm('Are you sure you want to delete this event?')) return;
  try {
    await API.admin.deleteEvent(id);
    showSuccess('Event deleted successfully');
    loadEvents();
  } catch (error) {
    showError('Error deleting event: ' + error.message);
  }
}

async function deleteExercise(id) {
  if (!confirm('Are you sure you want to delete this exercise?')) return;
  try {
    await API.admin.deleteExercise(id);
    showSuccess('Exercise deleted successfully');
    loadExercises();
  } catch (error) {
    showError('Error deleting exercise: ' + error.message);
  }
}

async function deleteOpportunity(id) {
  if (!confirm('Are you sure you want to delete this opportunity?')) return;
  try {
    await API.admin.deleteOpportunity(id);
    showSuccess('Opportunity deleted successfully');
    loadOpportunities();
  } catch (error) {
    showError('Error deleting opportunity: ' + error.message);
  }
}

// Handle resource form submission
if (typeof document !== 'undefined') {
  document.addEventListener('DOMContentLoaded', () => {
    const resourceForm = document.getElementById('form-resource');
    if (resourceForm) {
      resourceForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const resourceId = document.getElementById('resource-id').value;

        // Parse tags from comma-separated input
        const tagsInput = document.getElementById('resource-tags').value;
        const tags = tagsInput ? tagsInput.split(',').map(t => t.trim()).filter(t => t) : [];

        const resourceData = {
          title: document.getElementById('resource-title').value,
          description: document.getElementById('resource-description').value,
          content: document.getElementById('resource-content').value,
          category: document.getElementById('resource-category').value,
          tags: tags.length > 0 ? tags : null,
          external_url: document.getElementById('resource-url').value || null,
          is_published: document.getElementById('resource-published').checked
        };

        try {
          if (resourceId) {
            await API.admin.updateResource(resourceId, resourceData);
            showSuccess('Resource updated successfully!');
          } else {
            await API.admin.createResource(resourceData);
            showSuccess('Resource created successfully!');
          }
          closeModal('modal-resource');

          // Reload resources after a short delay to show success message
          setTimeout(() => {
            loadResources();
          }, 500);
        } catch (error) {
          showError('Error: ' + error.message);
        }
      });
    }
  });
}

// ========== Event Modal ==========
function openEventModal(eventId = null) {
  const modal = document.getElementById('modal-event');
  const title = document.getElementById('event-modal-title');

  if (eventId) {
    title.textContent = 'Edit Event';
    loadEventData(eventId);
  } else {
    title.textContent = 'Add New Event';
    document.getElementById('form-event').reset();
  }

  openModal('modal-event');
}

async function loadEventData(id) {
  try {
    const data = await API.admin.getEvent(id);
    const event = data.event || data;
    document.getElementById('event-id').value = event.id;
    document.getElementById('event-title').value = event.title;
    document.getElementById('event-description').value = event.description;
    document.getElementById('event-date').value = event.event_date.split('T')[0];
    document.getElementById('event-type').value = event.event_type;
    document.getElementById('event-start-time').value = event.start_time;
    document.getElementById('event-end-time').value = event.end_time;
    document.getElementById('event-location').value = event.location || '';
    document.getElementById('event-max-participants').value = event.max_participants || '';
    document.getElementById('event-online').checked = event.is_online;
    document.getElementById('event-published').checked = event.is_published;
  } catch (error) {
    alert('Error loading event: ' + error.message);
  }
}

function editEvent(id) {
  openEventModal(id);
}

// Handle event form submission
if (typeof document !== 'undefined') {
  document.addEventListener('DOMContentLoaded', () => {
    const eventForm = document.getElementById('form-event');
    if (eventForm) {
      eventForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const eventId = document.getElementById('event-id').value;
        const eventData = {
          title: document.getElementById('event-title').value,
          description: document.getElementById('event-description').value,
          event_date: document.getElementById('event-date').value,
          event_type: document.getElementById('event-type').value,
          start_time: document.getElementById('event-start-time').value,
          end_time: document.getElementById('event-end-time').value,
          location: document.getElementById('event-location').value || null,
          max_participants: document.getElementById('event-max-participants').value || null,
          is_online: document.getElementById('event-online').checked,
          is_published: document.getElementById('event-published').checked
        };

        try {
          if (eventId) {
            await API.admin.updateEvent(eventId, eventData);
            showSuccess('Event updated successfully!');
          } else {
            await API.admin.createEvent(eventData);
            showSuccess('Event created successfully!');
          }
          closeModal('modal-event');

          // Reload events after a short delay
          setTimeout(() => {
            loadEvents();
          }, 500);
        } catch (error) {
          showError('Error: ' + error.message);
        }
      });
    }
  });
}

// ========== Exercise Modal ==========
function openExerciseModal(exerciseId = null) {
  const modal = document.getElementById('modal-exercise');
  const title = document.getElementById('exercise-modal-title');

  if (exerciseId) {
    title.textContent = 'Edit Exercise';
    loadExerciseData(exerciseId);
  } else {
    title.textContent = 'Add New Exercise';
    document.getElementById('form-exercise').reset();
  }

  openModal('modal-exercise');
}

async function loadExerciseData(id) {
  try {
    const data = await API.admin.getExercise(id);
    const exercise = data.exercise || data;
    document.getElementById('exercise-id').value = exercise.id;
    document.getElementById('exercise-title').value = exercise.title;
    document.getElementById('exercise-description').value = exercise.description;
    document.getElementById('exercise-instructions').value = exercise.instructions;
    document.getElementById('exercise-type').value = exercise.type;
    document.getElementById('exercise-difficulty').value = exercise.difficulty;
    document.getElementById('exercise-duration').value = exercise.duration_minutes;
  } catch (error) {
    alert('Error loading exercise: ' + error.message);
  }
}

function editExercise(id) {
  openExerciseModal(id);
}

// Handle exercise form submission
if (typeof document !== 'undefined') {
  document.addEventListener('DOMContentLoaded', () => {
    const exerciseForm = document.getElementById('form-exercise');
    if (exerciseForm) {
      exerciseForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const exerciseId = document.getElementById('exercise-id').value;
        const exerciseData = {
          title: document.getElementById('exercise-title').value,
          description: document.getElementById('exercise-description').value,
          instructions: document.getElementById('exercise-instructions').value,
          type: document.getElementById('exercise-type').value,
          difficulty: document.getElementById('exercise-difficulty').value,
          duration_minutes: document.getElementById('exercise-duration').value
        };

        try {
          if (exerciseId) {
            await API.admin.updateExercise(exerciseId, exerciseData);
            showSuccess('Exercise updated successfully!');
          } else {
            await API.admin.createExercise(exerciseData);
            showSuccess('Exercise created successfully!');
          }
          closeModal('modal-exercise');

          // Reload exercises after a short delay
          setTimeout(() => {
            loadExercises();
          }, 500);
        } catch (error) {
          showError('Error: ' + error.message);
        }
      });
    }
  });
}

// ========== Opportunity Modal ==========
function openOpportunityModal(opportunityId = null) {
  const modal = document.getElementById('modal-opportunity');
  const title = document.getElementById('opportunity-modal-title');

  if (opportunityId) {
    title.textContent = 'Edit Opportunity';
    loadOpportunityData(opportunityId);
  } else {
    title.textContent = 'Add New Opportunity';
    document.getElementById('form-opportunity').reset();
  }

  openModal('modal-opportunity');
}

async function loadOpportunityData(id) {
  try {
    const data = await API.admin.getOpportunity(id);
    const opportunity = data.opportunity || data;
    document.getElementById('opportunity-id').value = opportunity.id;
    document.getElementById('opportunity-title').value = opportunity.title;
    document.getElementById('opportunity-description').value = opportunity.description;
    document.getElementById('opportunity-type').value = opportunity.opportunity_type;
    document.getElementById('opportunity-organization').value = opportunity.organization;
    document.getElementById('opportunity-location').value = opportunity.location || '';
    document.getElementById('opportunity-contact-email').value = opportunity.contact_email;
    document.getElementById('opportunity-apply-url').value = opportunity.apply_url || '';
    document.getElementById('opportunity-remote').checked = opportunity.is_remote;
    document.getElementById('opportunity-published').checked = opportunity.is_published;
  } catch (error) {
    alert('Error loading opportunity: ' + error.message);
  }
}

function editOpportunity(id) {
  openOpportunityModal(id);
}

// Handle opportunity form submission
if (typeof document !== 'undefined') {
  document.addEventListener('DOMContentLoaded', () => {
    const opportunityForm = document.getElementById('form-opportunity');
    if (opportunityForm) {
      opportunityForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const opportunityId = document.getElementById('opportunity-id').value;
        const opportunityData = {
          title: document.getElementById('opportunity-title').value,
          description: document.getElementById('opportunity-description').value,
          opportunity_type: document.getElementById('opportunity-type').value,
          organization: document.getElementById('opportunity-organization').value,
          location: document.getElementById('opportunity-location').value || null,
          contact_email: document.getElementById('opportunity-contact-email').value,
          apply_url: document.getElementById('opportunity-apply-url').value || null,
          is_remote: document.getElementById('opportunity-remote').checked,
          is_published: document.getElementById('opportunity-published').checked
        };

        try {
          if (opportunityId) {
            await API.admin.updateOpportunity(opportunityId, opportunityData);
            showSuccess('Opportunity updated successfully!');
          } else {
            await API.admin.createOpportunity(opportunityData);
            showSuccess('Opportunity created successfully!');
          }
          closeModal('modal-opportunity');

          // Reload opportunities after a short delay
          setTimeout(() => {
            loadOpportunities();
          }, 500);
        } catch (error) {
          showError('Error: ' + error.message);
        }
      });
    }
  });
}
