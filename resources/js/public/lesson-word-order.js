function normalizeSentence(value) {
    return value
        .normalize('NFKC')
        .trim()
        .toLocaleLowerCase()
        .replace(/[\u2018\u2019`]/g, "'")
        .replace(/\s+/g, ' ');
}

function shuffleWordTokens(container) {
    const tokens = [...container.querySelectorAll('.lesson-word-order-token')];

    for (let index = tokens.length - 1; index > 0; index -= 1) {
        const randomIndex = Math.floor(Math.random() * (index + 1));
        [tokens[index], tokens[randomIndex]] = [tokens[randomIndex], tokens[index]];
    }

    tokens.forEach(token => container.appendChild(token));
}

function updateWordOrderPlaceholder(item) {
    const selected = item.querySelector('[data-word-order-selected]');
    const placeholder = selected.querySelector('.lesson-word-order-placeholder');
    const hasTokens = selected.querySelector('.lesson-word-order-token') !== null;

    placeholder.hidden = hasTokens;
}

function initializeWordOrderExercise(exercise) {
    const items = [...exercise.querySelectorAll('[data-word-order-item]')];
    const checkButton = exercise.querySelector('[data-word-order-check]');
    const resetButton = exercise.querySelector('[data-word-order-reset]');
    const result = exercise.querySelector('[data-word-order-result]');

    if (!items.length || !checkButton || !resetButton || !result) {
        return;
    }

    items.forEach(item => {
        const bank = item.querySelector('[data-word-order-bank]');
        const selected = item.querySelector('[data-word-order-selected]');

        shuffleWordTokens(bank);
        updateWordOrderPlaceholder(item);

        item.addEventListener('click', event => {
            const token = event.target.closest('.lesson-word-order-token');
            if (!token) {
                return;
            }

            if (token.parentElement === bank) {
                selected.appendChild(token);
            } else {
                bank.appendChild(token);
            }

            item.classList.remove('is-correct', 'is-wrong');
            item.querySelector('[data-word-order-feedback]').textContent = '';
            updateWordOrderPlaceholder(item);
        });
    });

    checkButton.addEventListener('click', () => {
        let correct = 0;

        items.forEach(item => {
            const selected = item.querySelector('[data-word-order-selected]');
            const feedback = item.querySelector('[data-word-order-feedback]');
            const expected = item.dataset.answer ?? '';
            const actual = [...selected.querySelectorAll('.lesson-word-order-token')]
                .map(token => token.textContent.trim())
                .join(' ');
            const isCorrect = normalizeSentence(actual) === normalizeSentence(expected);

            item.classList.toggle('is-correct', isCorrect);
            item.classList.toggle('is-wrong', !isCorrect);

            if (isCorrect) {
                correct += 1;
                feedback.textContent = 'Правильно';
            } else {
                feedback.textContent = actual === ''
                    ? 'Складіть речення'
                    : `Правильна відповідь: ${expected}`;
            }
        });

        result.textContent = `Результат: ${correct} / ${items.length}`;
        result.classList.toggle('is-complete', correct === items.length);
    });

    resetButton.addEventListener('click', () => {
        items.forEach(item => {
            const bank = item.querySelector('[data-word-order-bank]');
            const selected = item.querySelector('[data-word-order-selected]');
            const feedback = item.querySelector('[data-word-order-feedback]');

            selected.querySelectorAll('.lesson-word-order-token').forEach(token => bank.appendChild(token));
            shuffleWordTokens(bank);
            item.classList.remove('is-correct', 'is-wrong');
            feedback.textContent = '';
            updateWordOrderPlaceholder(item);
        });

        result.textContent = '';
        result.classList.remove('is-complete');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-word-order-exercise]').forEach(initializeWordOrderExercise);
});
