function getApiBase() {
  return window.CONFIG ? CONFIG.API_BASE : '/backend/api';
}

async function apiCall(endpoint, options = {}) {
  const url = getApiBase() + endpoint;
  const token = localStorage.getItem('auth_token');

  const headers = {
    'Content-Type': 'application/json',
    ...(token && { 'X-Auth-Token': token }),
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

const API = {
  auth: {
    login: async (email, password) => {
      const data = await apiCall('/auth.php/login', {
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
      return await apiCall('/auth.php/register', {
        method: 'POST',
        body: JSON.stringify(userData)
      });
    },

    logout: async () => {
      try {
        await apiCall('/auth.php/logout', { method: 'POST' });
      } finally {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('current_user');
      }
    },

    getProfile: async () => {
      return await apiCall('/auth.php/me');
    },

    updateProfile: async (updates) => {
      return await apiCall('/auth.php/me', {
        method: 'PUT',
        body: JSON.stringify(updates)
      });
    },

    changePassword: async (currentPassword, newPassword) => {
      return await apiCall('/auth.php/change-password', {
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
    getAll: () => apiCall('/resources.php'),
    getById: (id) => apiCall(`/resources.php/${id}`),
    create: (data) => apiCall('/resources.php', { method: 'POST', body: JSON.stringify(data) }),
    update: (id, data) => apiCall(`/resources.php/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    delete: (id) => apiCall(`/resources.php/${id}`, { method: 'DELETE' })
  },

  exercises: {
    getAll: () => apiCall('/exercises.php'),
    getById: (id) => apiCall(`/exercises.php/${id}`),
    create: (data) => apiCall('/exercises.php', { method: 'POST', body: JSON.stringify(data) }),
    update: (id, data) => apiCall(`/exercises.php/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    delete: (id) => apiCall(`/exercises.php/${id}`, { method: 'DELETE' })
  },

  calendar: {
    getEvents: () => apiCall('/calendar.php'),
    getEvent: (id) => apiCall(`/calendar.php/${id}`),
    createEvent: (data) => apiCall('/calendar.php', { method: 'POST', body: JSON.stringify(data) }),
    updateEvent: (id, data) => apiCall(`/calendar.php/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteEvent: (id) => apiCall(`/calendar.php/${id}`, { method: 'DELETE' })
  },

  forum: {
    getPosts: () => apiCall('/forum.php'),
    getPost: (id) => apiCall(`/forum.php/${id}`),
    createPost: (data) => apiCall('/forum.php', { method: 'POST', body: JSON.stringify(data) }),
    updatePost: (id, data) => apiCall(`/forum.php/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deletePost: (id) => apiCall(`/forum.php/${id}`, { method: 'DELETE' })
  },

  admin: {
    getDashboardStats: () => apiCall('/admin.php/dashboard/stats'),
    getUsers: () => apiCall('/admin.php/users'),
    updateUser: (id, data) => apiCall(`/admin.php/users/${id}`, { method: 'PUT', body: JSON.stringify(data) }),
    deleteUser: (id) => apiCall(`/admin.php/users/${id}`, { method: 'DELETE' })
  }
};

window.API = API;
