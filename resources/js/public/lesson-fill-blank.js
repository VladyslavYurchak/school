function normalizeAnswer(value) {
    return value
        .normalize('NFKC')
        .trim()
        .toLocaleLowerCase()
        .replace(/[\u2018\u2019`]/g, "'")
        .replace(/\s+/g, ' ');
}

function shuffleSelectOptions(select) {
    const placeholder = select.querySelector('option[value=""]');
    const options = [...select.querySelectorAll('option:not([value=""])')];

    for (let index = options.length - 1; index > 0; index -= 1) {
        const randomIndex = Math.floor(Math.random() * (index + 1));
        [options[index], options[randomIndex]] = [options[randomIndex], options[index]];
    }

    if (placeholder) {
        select.appendChild(placeholder);
    }
    options.forEach(option => select.appendChild(option));
    select.value = '';
}

function initializeFillBlankExercise(exercise) {
    const items = [...exercise.querySelectorAll('[data-fill-blank-item]')];
    const checkButton = exercise.querySelector('[data-fill-blank-check]');
    const resetButton = exercise.querySelector('[data-fill-blank-reset]');
    const result = exercise.querySelector('[data-fill-blank-result]');

    if (!items.length || !checkButton || !resetButton || !result) {
        return;
    }

    exercise.querySelectorAll('select[data-fill-blank-control]').forEach(shuffleSelectOptions);

    function checkAnswers() {
        let correct = 0;

        items.forEach(item => {
            const control = item.querySelector('[data-fill-blank-control]');
            const feedback = item.querySelector('[data-fill-blank-feedback]');
            const expected = item.dataset.answer ?? '';
            const isCorrect = normalizeAnswer(control.value) === normalizeAnswer(expected);

            item.classList.toggle('is-correct', isCorrect);
            item.classList.toggle('is-wrong', !isCorrect);

            if (isCorrect) {
                correct += 1;
                feedback.textContent = 'Правильно';
            } else {
                feedback.textContent = control.value.trim() === ''
                    ? 'Заповніть відповідь'
                    : `Правильна відповідь: ${expected}`;
            }
        });

        result.textContent = `Результат: ${correct} / ${items.length}`;
        result.classList.toggle('is-complete', correct === items.length);
    }

    function resetExercise() {
        items.forEach(item => {
            const control = item.querySelector('[data-fill-blank-control]');
            const feedback = item.querySelector('[data-fill-blank-feedback]');

            control.value = '';
            item.classList.remove('is-correct', 'is-wrong');
            feedback.textContent = '';

            if (control.tagName === 'SELECT') {
                shuffleSelectOptions(control);
            }
        });

        result.textContent = '';
        result.classList.remove('is-complete');
    }

    checkButton.addEventListener('click', checkAnswers);
    resetButton.addEventListener('click', resetExercise);

    exercise.addEventListener('keydown', event => {
        if (event.key === 'Enter' && event.target.matches('input[data-fill-blank-control]')) {
            event.preventDefault();
            checkAnswers();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-fill-blank-exercise]').forEach(initializeFillBlankExercise);
});
