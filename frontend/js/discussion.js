document.addEventListener('DOMContentLoaded', async () => {
  console.log("discussion.js loaded");  

  const container = document.getElementById('discussionContainer');
  const actions = document.getElementById('discussionActions');
  const editBtn = document.getElementById('editPostBtn');
  const deleteBtn = document.getElementById('deletePostBtn');
  const repliesEl = document.getElementById('repliesContainer');
  const replyFormWrapper = document.getElementById('replyFormWrapper');

  const params = new URLSearchParams(window.location.search);
  const postId = params.get('id');

  if (!postId) {
    container.innerHTML = '<p>Invalid post ID.</p>';
    return;
  }

  try {
    const data = await API.forum.getPost(postId);
    const post = data.post;
    const user = API.auth.getCurrentUser();

    const createdStr = post.created_at ? new Date(post.created_at).toLocaleString() : '';

    container.innerHTML = `
      <h2 class="forum-thread__title">${escapeHtml(post.title)}</h2>
      <p class="forum-thread__meta">
        By ${escapeHtml(post.author || 'Member')} • ${createdStr}
      </p>
      <div class="forum-thread__content" style="margin-top:1rem;">
        ${nl2br(escapeHtml(post.content || ''))}
      </div>
    `;

    if (user && (user.id === post.user_id || user.role === 'admin')) {
      actions.style.display = 'flex';

      editBtn.onclick = async () => {
        const newTitle = prompt('Edit your post title:', post.title);
        if (!newTitle || !newTitle.trim()) return;
        try {
          await API.forum.updatePost(postId, {
            title: newTitle.trim(),
            content: post.content,
            category: post.category
          });
          location.reload();
        } catch (e) {
          alert(e.message || 'Failed to update post');
        }
      };

      deleteBtn.onclick = async () => {
        if (!confirm('Delete this post?')) return;
        try {
          await API.forum.deletePost(postId);
          window.location.href = 'forum.html';
        } catch (e) {
          alert(e.message || 'Failed to delete post');
        }
      };
    }

    await loadReplies(postId, user, repliesEl, replyFormWrapper);
  } catch (err) {
    console.error('Discussion load error:', err);
    container.innerHTML = '<p>Error loading post.</p>';
  }
});


async function loadReplies(postId, user, repliesEl, replyFormWrapper) {
  if (!repliesEl) return;

  repliesEl.innerHTML = '<h3 style="margin-bottom:0.5rem;">Replies</h3><p>Loading replies...</p>';

  try {
    const data = await API.forum.getReplies(postId);
    const replies = data.replies || [];

    if (!replies.length) {
      repliesEl.innerHTML = '<h3 style="margin-bottom:0.5rem;">Replies</h3><p>No replies yet.</p>';
    } else {
      const items = replies.map(r => {
        const createdStr = r.created_at ? new Date(r.created_at).toLocaleString() : '';
        return `
          <div class="forum-reply" style="margin-top:0.75rem; padding:0.75rem 1rem; background:#fff; border-radius:4px;">
            <p class="forum-reply__meta" style="font-size:0.85rem; color:var(--text-light); margin-bottom:0.25rem;">
              ${escapeHtml(r.author || 'Member')} • ${createdStr}
            </p>
            <div class="forum-reply__content">
              ${nl2br(escapeHtml(r.content || ''))}
            </div>
          </div>
        `;
      }).join('');

      repliesEl.innerHTML = '<h3 style="margin-bottom:0.5rem;">Replies</h3>' + items;
    }
  } catch (e) {
    console.error('Replies load error:', e);
    repliesEl.innerHTML = '<h3 style="margin-bottom:0.5rem;">Replies</h3><p>Error loading replies.</p>';
  }

  if (!replyFormWrapper) return;

  if (!user) {
    replyFormWrapper.innerHTML = '<p>Please log in to reply.</p>';
    return;
  }

  replyFormWrapper.innerHTML = `
    <form id="replyForm" class="forum-reply-form" style="margin-top:0.5rem;">
      <label for="replyContent">Write a reply:</label>
      <textarea id="replyContent" rows="4" required style="width:100%; margin-top:0.25rem;"></textarea>
      <button type="submit" class="btn btn--small" style="margin-top:0.5rem;">Post Reply</button>
    </form>
  `;

  const form = document.getElementById('replyForm');
  const textarea = document.getElementById('replyContent');

  form.onsubmit = async (e) => {
    e.preventDefault();
    const text = textarea.value.trim();
    if (!text) return;
    try {
      await API.forum.createReply(postId, { content: text });
      textarea.value = '';
      await loadReplies(postId, user, repliesEl, replyFormWrapper);
    } catch (err) {
      alert(err.message || 'Failed to post reply');
    }
  };
}


function escapeHtml(str) {
  return String(str || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function nl2br(str) {
  return str.replace(/\n/g, '<br>');
}
