function initializeTrueFalseExercise(exercise) {
    const items = [...exercise.querySelectorAll('[data-true-false-item]')];
    const checkButton = exercise.querySelector('[data-true-false-check]');
    const resetButton = exercise.querySelector('[data-true-false-reset]');
    const result = exercise.querySelector('[data-true-false-result]');

    if (!items.length || !checkButton || !resetButton || !result) {
        return;
    }

    items.forEach(item => {
        item.addEventListener('click', event => {
            const option = event.target.closest('[data-true-false-option]');
            if (!option) {
                return;
            }

            item.querySelectorAll('[data-true-false-option]').forEach(button => {
                button.classList.toggle('is-selected', button === option);
                button.setAttribute('aria-pressed', button === option ? 'true' : 'false');
            });

            item.dataset.selected = option.dataset.value;
            item.classList.remove('is-correct', 'is-wrong');
            item.querySelector('[data-true-false-feedback]').textContent = '';
        });
    });

    checkButton.addEventListener('click', () => {
        let correct = 0;

        items.forEach(item => {
            const selected = item.dataset.selected ?? '';
            const expected = item.dataset.answer ?? '';
            const explanation = item.dataset.explanation ?? '';
            const feedback = item.querySelector('[data-true-false-feedback]');
            const isCorrect = selected !== '' && selected === expected;

            item.classList.toggle('is-correct', isCorrect);
            item.classList.toggle('is-wrong', !isCorrect);

            if (isCorrect) {
                correct += 1;
                feedback.textContent = explanation ? `Правильно. ${explanation}` : 'Правильно';
            } else if (selected === '') {
                feedback.textContent = 'Оберіть відповідь';
            } else {
                const correctLabel = expected === 'true' ? 'Правда' : 'Неправда';
                feedback.textContent = explanation
                    ? `Правильна відповідь: ${correctLabel}. ${explanation}`
                    : `Правильна відповідь: ${correctLabel}`;
            }
        });

        result.textContent = `Результат: ${correct} / ${items.length}`;
        result.classList.toggle('is-complete', correct === items.length);
    });

    resetButton.addEventListener('click', () => {
        items.forEach(item => {
            delete item.dataset.selected;
            item.classList.remove('is-correct', 'is-wrong');
            item.querySelector('[data-true-false-feedback]').textContent = '';
            item.querySelectorAll('[data-true-false-option]').forEach(button => {
                button.classList.remove('is-selected');
                button.setAttribute('aria-pressed', 'false');
            });
        });

        result.textContent = '';
        result.classList.remove('is-complete');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-true-false-exercise]').forEach(initializeTrueFalseExercise);
});
