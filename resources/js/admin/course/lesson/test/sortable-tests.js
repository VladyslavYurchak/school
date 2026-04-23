import Sortable from 'sortablejs';

document.addEventListener('DOMContentLoaded', function () {
    console.log('SortableJS ініціалізується...');

    const sortableElement = document.getElementById('sortable-tests');

    if (!sortableElement) {
        console.warn("Елемент '#sortable-tests' не знайдено");
        return;
    }

    if (typeof updateOrderUrl === 'undefined' || !updateOrderUrl) {
        console.error('updateOrderUrl не визначений');
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
        console.error('CSRF-токен не знайдено');
        return;
    }

    console.log('Sortable знайдено, ініціалізуємо...');

    Sortable.create(sortableElement, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        dragClass: 'sortable-drag',

        onEnd: function () {
            const order = [];

            sortableElement.querySelectorAll('li[data-id]').forEach((el, index) => {
                order.push({
                    id: el.dataset.id,
                    position: index + 1
                });
            });

            console.log('Новий порядок:', order);

            fetch(updateOrderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ order })
            })
                .then(async response => {
                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(data.message || 'Помилка при оновленні порядку');
                    }

                    return data;
                })
                .then(data => {
                    if (data.success) {
                        console.log('Порядок оновлено');

                        sortableElement.querySelectorAll('li[data-id]').forEach((el, index) => {
                            const strong = el.querySelector('strong');
                            if (strong) {
                                strong.textContent = `#${index + 1}`;
                            }
                        });
                    } else {
                        alert(data.message || 'Помилка при оновленні порядку!');
                    }
                })
                .catch(error => {
                    console.error('Помилка fetch:', error);
                    alert('Не вдалося зберегти порядок тестів.');
                });
        }
    });
});
