/**
 * NEW HORIZON - Exercises Page
 * Loads and displays wellness exercises
 */

document.addEventListener('DOMContentLoaded', loadExercises);

async function loadExercises() {
  const container = document.querySelector('.exercises-stack');
  if (!container) return;

  container.innerHTML = '<p style="text-align: center; padding: 2rem;">Loading exercises...</p>';

  try {
    const data = await API.exercises.getAll();
    const exercises = data.exercises || data || [];

    if (exercises.length === 0) {
      container.innerHTML = '<p style="text-align: center; padding: 2rem;">No exercises available yet.</p>';
      return;
    }

    container.innerHTML = exercises.map(exercise => createExerciseCard(exercise)).join('');
  } catch (error) {
    console.error('Error loading exercises:', error);
    container.innerHTML = '<p style="text-align: center; padding: 2rem; color: #c0392b;">Failed to load exercises. Please try again later.</p>';
  }
}

function createExerciseCard(exercise) {
  const typeIcons = {
    breathing: '🌬️',
    meditation: '🧘',
    journaling: '📝',
    physical: '🏃',
    mindfulness: '🎯',
    grounding: '🌿'
  };

  const icon = typeIcons[exercise.type] || '✨';
  const difficulty = exercise.difficulty || 'beginner';
  const duration = exercise.duration || 5;

  return `
    <article class="exercise-card" data-id="${exercise.id}">
      <div class="exercise-card__icon">${icon}</div>
      <div class="exercise-card__content">
        <h2 class="exercise-card__title">${escapeHtml(exercise.title)}</h2>
        <p class="exercise-card__description">${escapeHtml(exercise.description || '')}</p>
        <div class="exercise-card__meta">
          <span class="exercise-card__type">${capitalizeFirst(exercise.type)}</span>
          <span class="exercise-card__difficulty">${capitalizeFirst(difficulty)}</span>
          <span class="exercise-card__duration">${duration} min</span>
        </div>
        <button class="btn exercise-card__btn" onclick="showExercise(${exercise.id})">Start Exercise</button>
      </div>
    </article>
  `;
}

async function showExercise(id) {
  try {
    const data = await API.exercises.getById(id);
    const exercise = data.exercise || data;

    // Create modal
    const modal = document.createElement('div');
    modal.className = 'exercise-modal';
    modal.innerHTML = `
      <div class="exercise-modal__overlay" onclick="closeExerciseModal()"></div>
      <div class="exercise-modal__content">
        <button class="exercise-modal__close" onclick="closeExerciseModal()">&times;</button>
        <h2>${escapeHtml(exercise.title)}</h2>
        <p>${escapeHtml(exercise.description || '')}</p>
        <div class="exercise-modal__instructions">
          <h3>Instructions</h3>
          <div>${formatInstructions(exercise.instructions || 'Follow along with this exercise.')}</div>
        </div>
        <p class="exercise-modal__duration">Duration: ${exercise.duration || 5} minutes</p>
      </div>
    `;

    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
  } catch (error) {
    console.error('Error loading exercise:', error);
    alert('Failed to load exercise details.');
  }
}

function closeExerciseModal() {
  const modal = document.querySelector('.exercise-modal');
  if (modal) {
    modal.remove();
    document.body.style.overflow = '';
  }
}

function formatInstructions(text) {
  return escapeHtml(text).replace(/\n/g, '<br>');
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function capitalizeFirst(str) {
  if (!str) return '';
  return str.charAt(0).toUpperCase() + str.slice(1);
}
