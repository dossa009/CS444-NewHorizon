/**
 * Exercises Page JavaScript
 * Loads and displays exercises from the database
 */

// Load all published exercises
async function loadExercises() {
  const container = document.querySelector('.exercises-stack');

  // Show loading state
  container.innerHTML = '<div class="loading-state">Loading exercises...</div>';

  try {
    const { exercises } = await API.exercises.getAll();

    if (!exercises || exercises.length === 0) {
      container.innerHTML = `
        <div class="empty-state">
          <h3>No exercises available yet</h3>
          <p>Check back soon for wellness exercises!</p>
        </div>
      `;
      return;
    }

    // Render exercises
    container.innerHTML = exercises.map(exercise => createExerciseCard(exercise)).join('');

  } catch (error) {
    console.error('Error loading exercises:', error);
    container.innerHTML = `
      <div class="error-state">
        <h3>Failed to load exercises</h3>
        <p>Please try again later.</p>
      </div>
    `;
  }
}

// Create HTML for a single exercise card
function createExerciseCard(exercise) {
  // Parse instructions (can be JSON array or plain text)
  let instructionsList = '';
  try {
    const instructions = typeof exercise.instructions === 'string'
      ? JSON.parse(exercise.instructions)
      : exercise.instructions;

    if (Array.isArray(instructions)) {
      instructionsList = instructions.map(step => `<li>${step}</li>`).join('');
    } else {
      instructionsList = `<li>${exercise.instructions}</li>`;
    }
  } catch (e) {
    // If parsing fails, treat as plain text
    instructionsList = `<li>${exercise.instructions}</li>`;
  }

  // Determine exercise type/category for styling
  const exerciseType = exercise.category || 'general';

  // Format difficulty badge
  const difficultyBadge = exercise.difficulty
    ? `<span class="difficulty-badge difficulty-${exercise.difficulty}">${exercise.difficulty}</span>`
    : '';

  // Format duration
  const durationText = exercise.duration_minutes
    ? `<span class="duration-badge">${exercise.duration_minutes} min</span>`
    : '';

  return `
    <article class="exercises-card" data-type="${exerciseType}" data-id="${exercise.id}">
      <div class="exercises-card__header">
        <h2 class="exercises-card__title">${exercise.title}</h2>
        <div class="exercises-card__meta">
          ${difficultyBadge}
          ${durationText}
        </div>
      </div>
      <p class="exercises-card__content">
        ${exercise.description}
      </p>
      <details class="exercises-details">
        <summary>How to do it</summary>
        <ol class="exercises-steps">
          ${instructionsList}
        </ol>
      </details>
      ${createCompleteButton(exercise.id)}
    </article>
  `;
}

// Create complete button (only for logged-in users)
function createCompleteButton(exerciseId) {
  if (!API.auth.isLoggedIn()) {
    return '';
  }

  return `
    <button class="btn-complete" onclick="completeExercise(${exerciseId})">
      Mark as Completed
    </button>
  `;
}

// Handle exercise completion
async function completeExercise(exerciseId) {
  if (!API.auth.isLoggedIn()) {
    alert('Please log in to track your progress');
    window.location.href = 'pages/login.html';
    return;
  }

  try {
    await API.exercises.completeExercise(exerciseId, {
      notes: '',
      rating: null
    });

    // Show success feedback
    const card = document.querySelector(`[data-id="${exerciseId}"]`);
    const button = card.querySelector('.btn-complete');

    if (button) {
      button.textContent = '✓ Completed!';
      button.disabled = true;
      button.classList.add('completed');
    }

    // Optional: Show a toast/notification
    showNotification('Exercise completed! Great job!', 'success');

  } catch (error) {
    console.error('Error completing exercise:', error);
    showNotification('Failed to track completion. Please try again.', 'error');
  }
}

// Simple notification function
function showNotification(message, type = 'info') {
  // Create notification element
  const notification = document.createElement('div');
  notification.className = `notification notification-${type}`;
  notification.textContent = message;

  // Add to body
  document.body.appendChild(notification);

  // Show notification
  setTimeout(() => {
    notification.classList.add('show');
  }, 10);

  // Remove after 3 seconds
  setTimeout(() => {
    notification.classList.remove('show');
    setTimeout(() => {
      notification.remove();
    }, 300);
  }, 3000);
}

// Initialize on page load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', loadExercises);
} else {
  loadExercises();
}

// Make completeExercise globally available
window.completeExercise = completeExercise;
