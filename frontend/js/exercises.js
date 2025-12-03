document.addEventListener('DOMContentLoaded', loadExercises);

async function loadExercises() {
  const container = document.querySelector('.exercises-stack');
  if (!container) return;

  container.innerHTML = '<p style="text-align: center; padding: 2rem;">Loading exercises...</p>';

  try {
    const data = await API.exercises.getAll();
    const exercises = data.exercises || [];

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
  return `
    <article class="exercise-card" data-id="${exercise.id}">
      <div class="exercise-card__content">
        <h2 class="exercise-card__title">${escapeHtml(exercise.name)}</h2>
        <p class="exercise-card__description">${escapeHtml(exercise.description || 'No description available.')}</p>
        ${exercise.exercise_url ? `
          <a href="${exercise.exercise_url}" target="_blank" class="btn exercise-card__btn">View Exercise</a>
        ` : ''}
      </div>
    </article>
  `;
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
