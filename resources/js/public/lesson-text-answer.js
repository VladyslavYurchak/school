function normalizeTextAnswer(value) {
    return value
        .normalize('NFKC')
        .trim()
        .toLocaleLowerCase()
        .replace(/[\u2018\u2019`]/g, "'")
        .replace(/[.,!?;:"]/g, '')
        .replace(/\s+/g, ' ');
}

function acceptedAnswersFor(item) {
    try {
        const answers = JSON.parse(item.dataset.acceptedAnswers ?? '[]');
        return Array.isArray(answers) ? answers : [];
    } catch {
        return [];
    }
}

function initializeTextAnswerExercise(exercise) {
    const items = [...exercise.querySelectorAll('[data-text-answer-item]')];
    const checkButton = exercise.querySelector('[data-text-answer-check]');
    const resetButton = exercise.querySelector('[data-text-answer-reset]');
    const result = exercise.querySelector('[data-text-answer-result]');

    if (!items.length || !checkButton || !resetButton || !result) {
        return;
    }

    function checkAnswers() {
        let correct = 0;

        items.forEach(item => {
            const control = item.querySelector('[data-text-answer-control]');
            const feedback = item.querySelector('[data-text-answer-feedback]');
            const actual = normalizeTextAnswer(control.value);
            const accepted = acceptedAnswersFor(item).map(normalizeTextAnswer);
            const isCorrect = actual !== '' && accepted.includes(actual);

            item.classList.toggle('is-correct', isCorrect);
            item.classList.toggle('is-wrong', !isCorrect);

            if (isCorrect) {
                correct += 1;
                feedback.textContent = 'Правильно';
            } else {
                feedback.textContent = actual === ''
                    ? 'Введіть відповідь'
                    : `Правильна відповідь: ${item.dataset.primaryAnswer ?? ''}`;
            }
        });

        result.textContent = `Результат: ${correct} / ${items.length}`;
        result.classList.toggle('is-complete', correct === items.length);
    }

    function resetExercise() {
        items.forEach(item => {
            const control = item.querySelector('[data-text-answer-control]');
            const feedback = item.querySelector('[data-text-answer-feedback]');

            control.value = '';
            item.classList.remove('is-correct', 'is-wrong');
            feedback.textContent = '';
        });

        result.textContent = '';
        result.classList.remove('is-complete');
    }

    checkButton.addEventListener('click', checkAnswers);
    resetButton.addEventListener('click', resetExercise);

    exercise.addEventListener('keydown', event => {
        if (event.key === 'Enter' && event.target.matches('[data-text-answer-control]')) {
            event.preventDefault();
            checkAnswers();
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-text-answer-exercise]').forEach(initializeTextAnswerExercise);
});
