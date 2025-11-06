/**
 * NEW HORIZON - API Client
 * Handles all communication with the backend API
 */

const API_BASE_URL = 'http://localhost:3000/api';

// Get auth token from localStorage
function getAuthToken() {
  return localStorage.getItem('auth_token');
}

// Set auth token in localStorage
function setAuthToken(token) {
  if (token) {
    localStorage.setItem('auth_token', token);
  } else {
    localStorage.removeItem('auth_token');
  }
}

// Get current user from localStorage
function getCurrentUser() {
  const userStr = localStorage.getItem('current_user');
  return userStr ? JSON.parse(userStr) : null;
}

// Set current user in localStorage
function setCurrentUser(user) {
  if (user) {
    localStorage.setItem('current_user', JSON.stringify(user));
  } else {
    localStorage.removeItem('current_user');
  }
}

// Generic API request function
async function apiRequest(endpoint, options = {}) {
  const url = `${API_BASE_URL}${endpoint}`;
  const token = getAuthToken();

  const config = {
    method: options.method || 'GET',
    headers: {
      'Content-Type': 'application/json',
      ...(token && { 'Authorization': `Bearer ${token}` }),
      ...options.headers
    }
  };

  if (options.body) {
    config.body = JSON.stringify(options.body);
  }

  try {
    const response = await fetch(url, config);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error || `HTTP error! status: ${response.status}`);
    }

    return data;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

// Authentication APIs
const authAPI = {
  async login(email, password) {
    const data = await apiRequest('/auth/login', {
      method: 'POST',
      body: { email, password }
    });
    setAuthToken(data.token);
    setCurrentUser(data.user);
    return data;
  },

  async register(userData) {
    return await apiRequest('/auth/register', {
      method: 'POST',
      body: userData
    });
  },

  async logout() {
    try {
      await apiRequest('/auth/logout', { method: 'POST' });
    } finally {
      setAuthToken(null);
      setCurrentUser(null);
    }
  },

  async getProfile() {
    return await apiRequest('/auth/me');
  },

  async updateProfile(updates) {
    return await apiRequest('/auth/me', {
      method: 'PUT',
      body: updates
    });
  },

  async changePassword(current_password, new_password) {
    return await apiRequest('/auth/change-password', {
      method: 'PUT',
      body: { current_password, new_password }
    });
  },

  isLoggedIn() {
    return !!getAuthToken();
  },

  getCurrentUser() {
    return getCurrentUser();
  }
};

// Resources APIs
const resourcesAPI = {
  async getAll(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/resources${query ? '?' + query : ''}`);
  },

  async getById(id) {
    return await apiRequest(`/resources/${id}`);
  },

  async create(resourceData) {
    return await apiRequest('/resources', {
      method: 'POST',
      body: resourceData
    });
  },

  async update(id, updates) {
    return await apiRequest(`/resources/${id}`, {
      method: 'PUT',
      body: updates
    });
  },

  async delete(id) {
    return await apiRequest(`/resources/${id}`, {
      method: 'DELETE'
    });
  }
};

// Forum APIs
const forumAPI = {
  async getPosts(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/forum${query ? '?' + query : ''}`);
  },

  async getPost(id) {
    return await apiRequest(`/forum/${id}`);
  },

  async createPost(postData) {
    return await apiRequest('/forum', {
      method: 'POST',
      body: postData
    });
  },

  async addComment(postId, commentData) {
    return await apiRequest(`/forum/${postId}/comments`, {
      method: 'POST',
      body: commentData
    });
  },

  async deletePost(id) {
    return await apiRequest(`/forum/${id}`, {
      method: 'DELETE'
    });
  }
};

// Calendar APIs
const calendarAPI = {
  async getEvents(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/calendar${query ? '?' + query : ''}`);
  },

  async getEvent(id) {
    return await apiRequest(`/calendar/${id}`);
  },

  async registerForEvent(id) {
    return await apiRequest(`/calendar/${id}/register`, {
      method: 'POST'
    });
  },

  async cancelRegistration(id) {
    return await apiRequest(`/calendar/${id}/register`, {
      method: 'DELETE'
    });
  }
};

// Exercises APIs
const exercisesAPI = {
  async getAll(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/exercises${query ? '?' + query : ''}`);
  },

  async getById(id) {
    return await apiRequest(`/exercises/${id}`);
  },

  async completeExercise(id, data) {
    return await apiRequest(`/exercises/${id}/complete`, {
      method: 'POST',
      body: data
    });
  },

  async getProgress() {
    return await apiRequest('/exercises/progress/me');
  }
};

// Opportunities APIs
const opportunitiesAPI = {
  async getAll(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/opportunities${query ? '?' + query : ''}`);
  },

  async getById(id) {
    return await apiRequest(`/opportunities/${id}`);
  }
};

// Contact API
const contactAPI = {
  async sendMessage(messageData) {
    return await apiRequest('/contact', {
      method: 'POST',
      body: messageData
    });
  }
};

// Admin APIs
const adminAPI = {
  async getDashboardStats() {
    return await apiRequest('/admin/dashboard/stats');
  },

  async getUsers(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/admin/users${query ? '?' + query : ''}`);
  },

  async updateUser(id, updates) {
    return await apiRequest(`/admin/users/${id}`, {
      method: 'PUT',
      body: updates
    });
  },

  async deleteUser(id) {
    return await apiRequest(`/admin/users/${id}`, {
      method: 'DELETE'
    });
  },

  async getAuditLog(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/admin/audit-log${query ? '?' + query : ''}`);
  },

  // Admin Resources Management (GET via /admin, CRUD via /resources)
  async getResources(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/admin/resources${query ? '?' + query : ''}`);
  },

  async getResource(id) {
    return await apiRequest(`/resources/${id}`);
  },

  async createResource(resourceData) {
    return await apiRequest('/resources', {
      method: 'POST',
      body: resourceData
    });
  },

  async updateResource(id, updates) {
    return await apiRequest(`/resources/${id}`, {
      method: 'PUT',
      body: updates
    });
  },

  async deleteResource(id) {
    return await apiRequest(`/resources/${id}`, {
      method: 'DELETE'
    });
  },

  // Admin Forum Management
  async getForumPosts(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/admin/forum-posts${query ? '?' + query : ''}`);
  },

  async moderatePost(id, status) {
    return await apiRequest(`/forum/${id}/moderate`, {
      method: 'PUT',
      body: { status }
    });
  },

  async deletePost(id) {
    return await apiRequest(`/forum/${id}`, {
      method: 'DELETE'
    });
  },

  // Admin Calendar Management (GET via /admin, CRUD via /calendar)
  async getEvents(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/admin/calendar-events${query ? '?' + query : ''}`);
  },

  async getEvent(id) {
    return await apiRequest(`/calendar/${id}`);
  },

  async createEvent(eventData) {
    return await apiRequest('/calendar', {
      method: 'POST',
      body: eventData
    });
  },

  async updateEvent(id, updates) {
    return await apiRequest(`/calendar/${id}`, {
      method: 'PUT',
      body: updates
    });
  },

  async deleteEvent(id) {
    return await apiRequest(`/calendar/${id}`, {
      method: 'DELETE'
    });
  },

  // Admin Exercises Management (GET via /admin, CRUD via /exercises)
  async getExercises(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/admin/exercises${query ? '?' + query : ''}`);
  },

  async getExercise(id) {
    return await apiRequest(`/exercises/${id}`);
  },

  async createExercise(exerciseData) {
    return await apiRequest('/exercises', {
      method: 'POST',
      body: exerciseData
    });
  },

  async updateExercise(id, updates) {
    return await apiRequest(`/exercises/${id}`, {
      method: 'PUT',
      body: updates
    });
  },

  async deleteExercise(id) {
    return await apiRequest(`/exercises/${id}`, {
      method: 'DELETE'
    });
  },

  // Admin Opportunities Management (GET via /admin, CRUD via /opportunities)
  async getOpportunities(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/admin/opportunities${query ? '?' + query : ''}`);
  },

  async getOpportunity(id) {
    return await apiRequest(`/opportunities/${id}`);
  },

  async createOpportunity(opportunityData) {
    return await apiRequest('/opportunities', {
      method: 'POST',
      body: opportunityData
    });
  },

  async updateOpportunity(id, updates) {
    return await apiRequest(`/opportunities/${id}`, {
      method: 'PUT',
      body: updates
    });
  },

  async deleteOpportunity(id) {
    return await apiRequest(`/opportunities/${id}`, {
      method: 'DELETE'
    });
  },

  // Admin Contact Messages
  async getContactMessages(params = {}) {
    const query = new URLSearchParams(params).toString();
    return await apiRequest(`/admin/contact-messages${query ? '?' + query : ''}`);
  },

  async updateContactMessage(id, updates) {
    return await apiRequest(`/admin/contact-messages/${id}`, {
      method: 'PUT',
      body: updates
    });
  }
};

// Export all APIs
const API = {
  auth: authAPI,
  resources: resourcesAPI,
  forum: forumAPI,
  calendar: calendarAPI,
  exercises: exercisesAPI,
  opportunities: opportunitiesAPI,
  contact: contactAPI,
  admin: adminAPI
};

// Make API available globally
if (typeof window !== 'undefined') {
  window.API = API;
}
