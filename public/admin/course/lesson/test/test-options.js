document.addEventListener('DOMContentLoaded', function() {
    console.log("test-options.js завантажено");
    const addOptionButton = document.getElementById('add-option');
    const optionsContainer = document.querySelector('.options-container');

    let optionIndex = 3; // Починаємо з 3, бо у нас вже є 3 варіанти

    addOptionButton.addEventListener('click', function() {
        const currentOptions = optionsContainer.querySelectorAll('.d-flex').length;

        if (currentOptions >= 5) {
            alert('Максимум 5 варіантів відповідей.');
            return;
        }

        const newOptionHTML = `
                    <div class="d-flex align-items-center mb-2" data-index="${optionIndex}">
                        <input type="text" name="options[new][${optionIndex}][option_text]" class="form-control me-2 option-input" placeholder="Новий варіант" />
                        <label class="custom-checkbox">
                            <input type="checkbox" name="options[new][${optionIndex}][is_correct]" value="1">
                            <span class="checkmark"></span> Правильна
                        </label>
                        <button type="button" class="btn btn-danger btn-sm remove-option" data-index="${optionIndex}">Видалити</button>
                    </div>`;

        optionsContainer.insertAdjacentHTML('beforeend', newOptionHTML);
        optionIndex++;
    });

    optionsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-option')) {
            const index = e.target.dataset.index;
            const optionElement = optionsContainer.querySelector(`[data-index="${index}"]`);

            if (optionElement) {
                optionElement.remove();
            }
        }
    });
});
