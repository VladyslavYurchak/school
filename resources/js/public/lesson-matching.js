function shuffleChildren(container) {
    const items = [...container.children];

    for (let index = items.length - 1; index > 0; index -= 1) {
        const randomIndex = Math.floor(Math.random() * (index + 1));
        [items[index], items[randomIndex]] = [items[randomIndex], items[index]];
    }

    items.forEach(item => container.appendChild(item));
}

function initializeMatchingExercise(exercise) {
    const prompts = exercise.querySelector('[data-matching-prompts]');
    const answers = exercise.querySelector('[data-matching-answers]');
    const progress = exercise.querySelector('[data-matching-progress]');
    const complete = exercise.querySelector('[data-matching-complete]');
    const reset = exercise.querySelector('[data-matching-reset]');

    if (!prompts || !answers || !progress || !complete || !reset) {
        return;
    }

    const total = prompts.children.length;
    let selectedPrompt = null;
    let matched = 0;

    function updateProgress() {
        progress.textContent = `З’єднано: ${matched} / ${total}`;
        complete.hidden = matched !== total;
    }

    function clearSelection() {
        selectedPrompt?.classList.remove('is-selected');
        selectedPrompt = null;
    }

    prompts.addEventListener('click', event => {
        const button = event.target.closest('.lesson-matching-option:not(.is-matched)');
        if (!button) {
            return;
        }

        clearSelection();
        selectedPrompt = button;
        selectedPrompt.classList.add('is-selected');
    });

    answers.addEventListener('click', event => {
        const answer = event.target.closest('.lesson-matching-option:not(.is-matched)');
        if (!answer || !selectedPrompt) {
            return;
        }

        if (answer.dataset.pairId === selectedPrompt.dataset.pairId) {
            selectedPrompt.classList.remove('is-selected');
            selectedPrompt.classList.add('is-matched');
            answer.classList.add('is-matched');
            selectedPrompt.disabled = true;
            answer.disabled = true;
            selectedPrompt = null;
            matched += 1;
            updateProgress();
            return;
        }

        const wrongPrompt = selectedPrompt;
        wrongPrompt.classList.add('is-wrong');
        answer.classList.add('is-wrong');

        window.setTimeout(() => {
            wrongPrompt.classList.remove('is-wrong');
            answer.classList.remove('is-wrong');
        }, 550);
    });

    reset.addEventListener('click', () => {
        selectedPrompt = null;
        matched = 0;

        exercise.querySelectorAll('.lesson-matching-option').forEach(button => {
            button.disabled = false;
            button.classList.remove('is-selected', 'is-matched', 'is-wrong');
        });

        shuffleChildren(prompts);
        shuffleChildren(answers);
        updateProgress();
    });

    shuffleChildren(answers);
    updateProgress();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-matching-exercise]').forEach(initializeMatchingExercise);
});
