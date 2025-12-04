document.addEventListener('DOMContentLoaded', () => {
  // make sure user is logged in (JWT)
  if (!API.auth.isLoggedIn()) {
    window.location.href = CONFIG.page('login.html');
    return;
  }

  const form      = document.getElementById('new-discussion-form');
  const titleEl   = document.getElementById('discussion-title');
  const contentEl = document.getElementById('discussion-content');
  const catEl     = document.getElementById('discussion-category');
  const errorBox  = document.getElementById('error-message');

  function showError(msg) {
    if (!errorBox) return;
    errorBox.textContent = msg;
    errorBox.style.display = 'block';
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    errorBox.style.display = 'none';

    const title   = titleEl.value.trim();
    const content = contentEl.value.trim();
    const category = catEl.value;

    if (!title || !content) {
      showError('Title and content are required.');
      return;
    }

    try {
      await API.forum.createPost({ title, content, category });
      window.location.href = CONFIG.page('forum.html');
    } catch (err) {
      console.error(err);
      showError(err.message || 'Failed to create discussion.');
    }
  });
});
