document.addEventListener('DOMContentLoaded', function () {
    const addOptionButton = document.getElementById('add-option');
    const optionsContainer = document.querySelector('.options-container');

    if (!addOptionButton || !optionsContainer) {
        return;
    }

    let optionIndex = optionsContainer.querySelectorAll('[data-index]').length;

    function optionRows() {
        return Array.from(optionsContainer.querySelectorAll('.option-row'));
    }

    function updateControls() {
        const count = optionRows().length;

        optionRows().forEach(optionRow => {
            const removeButton = optionRow.querySelector('.remove-option');

            if (removeButton) {
                removeButton.disabled = count <= 3;
            }
        });

        addOptionButton.disabled = count >= 5;
    }

    addOptionButton.addEventListener('click', function () {
        if (optionRows().length >= 5) {
            alert('У тесті може бути не більше 5 відповідей.');
            updateControls();
            return;
        }

        const newOptionHTML = `
            <div class="option-row" data-index="${optionIndex}">
                <input
                    type="text"
                    name="options[new][${optionIndex}][option_text]"
                    class="form-control option-input"
                    placeholder="Нова відповідь"
                    maxlength="1000"
                />
                <label class="custom-checkbox">
                    <input type="checkbox" name="options[new][${optionIndex}][is_correct]" value="1">
                    <span class="checkmark"></span>
                    Правильна
                </label>
                <button
                    type="button"
                    class="admin-btn-danger remove-option"
                    data-index="${optionIndex}"
                >
                    <i class="bi bi-trash"></i>
                    Видалити
                </button>
            </div>
        `;

        optionsContainer.insertAdjacentHTML('beforeend', newOptionHTML);
        optionIndex++;
        updateControls();
    });

    optionsContainer.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.remove-option');
        if (!removeButton) {
            return;
        }

        if (optionRows().length <= 3) {
            alert('У тесті має бути щонайменше 3 відповіді.');
            updateControls();
            return;
        }

        removeButton.closest('.option-row')?.remove();
        updateControls();
    });

    updateControls();
});
