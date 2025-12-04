document.addEventListener('DOMContentLoaded', () => {
  const threadsEl     = document.getElementById('forumThreads');
  const sortEl        = document.getElementById('forumSort');
  const searchInput   = document.getElementById('forumSearch');
  const newBtn        = document.getElementById('newDiscussionBtn');
  const categoryLinks = document.querySelectorAll('.forum-category');

  let activeCategory = 'general';

  if (!threadsEl) return;

  async function loadDiscussions() {
    const sort   = sortEl ? sortEl.value : 'new';
    const search = searchInput ? searchInput.value.trim() : '';

    threadsEl.innerHTML = '<p>Loading discussions...</p>';

    const params = { sort };
    if (search) params.search = search;
    if (activeCategory && activeCategory !== 'general') {
      params.category = activeCategory;
    }

    try {
      const data = await API.forum.getPosts(params);
      const posts = data.posts || [];

      if (!posts.length) {
        threadsEl.innerHTML = '<p>No discussions found.</p>';
        return;
      }

      threadsEl.innerHTML = '';

      posts.forEach(p => {
        const div = document.createElement('div');
        div.className = 'forum-thread';

        const created = p.created_at ? new Date(p.created_at) : null;
        const createdStr = created ? created.toLocaleString() : '';

        const snippetRaw = p.content || '';
        const snippetShort = snippetRaw.length > 160
          ? snippetRaw.slice(0, 160) + '…'
          : snippetRaw;

        div.innerHTML = `
          <div class="forum-thread__top">
            <span class="forum-thread__title">
              ${escapeHtml(p.title)}
            </span>
            <button class="forum-thread__manage" data-id="${p.id}">
              View &amp; Reply &gt;
            </button>
          </div>
          <div class="forum-thread__meta">
            <span>By ${escapeHtml(p.author || 'Member')}</span>
            <span> • </span>
            <span>${createdStr}</span>
            ${p.category ? `<span> • </span><span>${escapeHtml(prettyCategory(p.category))}</span>` : ''}
          </div>
          ${snippetShort
            ? `<div class="forum-thread__snippet">
                 ${escapeHtml(snippetShort)}
               </div>`
            : ''
          }
        `;

        threadsEl.appendChild(div);
      });
    } catch (err) {
      console.error(err);
      threadsEl.innerHTML = '<p>Error loading discussions.</p>';
    }
  }

  function prettyCategory(cat) {
    switch (cat) {
      case 'techniques': return 'Techniques & Strategies';
      case 'mental':     return 'Mental Health';
      case 'physical':   return 'Physical Wellness';
      default:           return 'General Discussion';
    }
  }

  function escapeHtml(str) {
    return String(str || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;/')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  if (sortEl) {
    sortEl.addEventListener('change', loadDiscussions);
  }

  if (searchInput) {
    searchInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        loadDiscussions();
      }
    });
  }

  if (categoryLinks.length) {
    categoryLinks.forEach(link => {
      if (link.classList.contains('forum-category--active')) {
        activeCategory = link.dataset.category || 'general';
      }
    });

    categoryLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        activeCategory = link.dataset.category || '';

        categoryLinks.forEach(l => l.classList.remove('forum-category--active'));
        link.classList.add('forum-category--active');

        loadDiscussions();
      });
    });
  }

  if (newBtn) {
    newBtn.addEventListener('click', () => {
      if (!API.auth.isLoggedIn()) {
        window.location.href = CONFIG.page('login.html');
        return;
      }
      window.location.href = CONFIG.page('new-discussion.html');
    });
  }

  threadsEl.addEventListener('click', (e) => {
    if (e.target.classList.contains('forum-thread__manage')) {
      const id = e.target.getAttribute('data-id');
      window.location.href = `discussion.html?id=${encodeURIComponent(id)}`;
    }
  });

  loadDiscussions();
});
