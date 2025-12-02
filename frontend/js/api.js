/**
 * NEW HORIZON - API Client
 * Handles all API calls to the backend
 */

// Get API base from config
function getApiBase() {
  return window.CONFIG ? CONFIG.API_BASE : '/backend/api';
}

// Helper function for API calls
async function apiCall(endpoint, options = {}) {
  const url = getApiBase() + endpoint;
  const token = localStorage.getItem('auth_token');

  const headers = {
    'Content-Type': 'application/json',
    ...(token && { 'Authorization': `Bearer ${token}` }),
    ...options.headers
  };

  try {
    const response = await fetch(url, { ...options, headers });
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.error || data.message || 'API request failed');
    }

    return data;
  } catch (error) {
    console.error('API Error:', error);
    throw error;
  }
}

// API object with all endpoints
const API = {
  auth: {
    login: async (email, password) => {
      const data = await apiCall('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password })
      });
      if (data.token) {
        localStorage.setItem('auth_token', data.token);
        localStorage.setItem('current_user', JSON.stringify(data.user));
      }
      return data;
    },

    register: async (userData) => {
      return await apiCall('/auth/register', {
        method: 'POST',
        body: JSON.stringify(userData)
      });
    },

    logout: async () => {
      try {
        await apiCall('/auth/logout', { method: 'POST' });
      } finally {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('current_user');
      }
    },

    getProfile: async () => {
      return await apiCall('/auth/me');
    },

    updateProfile: async (updates) => {
      return await apiCall('/auth/me', {
        method: 'PUT',
        body: JSON.stringify(updates)
      });
    },

    changePassword: async (currentPassword, newPassword) => {
      return await apiCall('/auth/change-password', {
        method: 'POST',
        body: JSON.stringify({ current_password: currentPassword, new_password: newPassword })
      });
    },

    isLoggedIn: () => {
      return !!localStorage.getItem('auth_token');
    },

    getCurrentUser: () => {
      const userStr = localStorage.getItem('current_user');
      return userStr ? JSON.parse(userStr) : null;
    }
  },

  resources: {
    getAll: () => apiCall('/resources'),
    getById: (id) => apiCall(`/resources/${id}`),
    create: (data) => apiCall('/resources', { method: 'POST', body: JSON.stringify(data) }),
    update: (id, data) => apiCall(`/resources/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    delete: (id) => apiCall(`/resources/${id}`, { method: 'DELETE' })
  },

  exercises: {
    getAll: () => apiCall('/exercises'),
    getById: (id) => apiCall(`/exercises/${id}`),
    create: (data) => apiCall('/exercises', { method: 'POST', body: JSON.stringify(data) }),
    update: (id, data) => apiCall(`/exercises/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    delete: (id) => apiCall(`/exercises/${id}`, { method: 'DELETE' })
  },

  calendar: {
    getEvents: () => apiCall('/calendar'),
    getEvent: (id) => apiCall(`/calendar/${id}`),
    createEvent: (data) => apiCall('/calendar', { method: 'POST', body: JSON.stringify(data) }),
    updateEvent: (id, data) => apiCall(`/calendar/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteEvent: (id) => apiCall(`/calendar/${id}`, { method: 'DELETE' })
  },

  forum: {
    getPosts: () => apiCall('/forum'),
    getPost: (id) => apiCall(`/forum/${id}`),
    createPost: (data) => apiCall('/forum', { method: 'POST', body: JSON.stringify(data) }),
    updatePost: (id, data) => apiCall(`/forum/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deletePost: (id) => apiCall(`/forum/${id}`, { method: 'DELETE' })
  },

  opportunities: {
    getAll: () => apiCall('/opportunities'),
    getById: (id) => apiCall(`/opportunities/${id}`),
    create: (data) => apiCall('/opportunities', { method: 'POST', body: JSON.stringify(data) }),
    update: (id, data) => apiCall(`/opportunities/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    delete: (id) => apiCall(`/opportunities/${id}`, { method: 'DELETE' })
  },

  contact: {
    sendMessage: (data) => apiCall('/contact', { method: 'POST', body: JSON.stringify(data) }),
    getMessages: () => apiCall('/contact')
  },

  admin: {
    getDashboardStats: () => apiCall('/admin/dashboard/stats'),
    getUsers: () => apiCall('/admin/users'),
    updateUser: (id, data) => apiCall(`/admin/users/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteUser: (id) => apiCall(`/admin/users/${id}`, { method: 'DELETE' })
  }
};

// Make API globally available
window.API = API;
