document.addEventListener('DOMContentLoaded', function () {
    console.log("SortableJS ініціалізується...");

    const sortableElement = document.getElementById('sortable-tests');
    if (!sortableElement) {
        console.error("Елемент 'sortable-tests' не знайдено!");
        return;
    }

    console.log("Sortable знайдено, ініціалізуємо...");

    Sortable.create(sortableElement, {
        animation: 150,
        onUpdate: function () {
            let order = [];
            sortableElement.querySelectorAll('li').forEach((el, index) => {
                order.push({
                    id: el.dataset.id,
                    position: index + 1
                });
            });

            console.log("Новий порядок:", order);

            // Отримуємо CSRF-токен без помилки
            const csrfToken = document.querySelector('meta[name="csrf-token"]');
            if (!csrfToken) {
                console.error("CSRF-токен не знайдено!");
                return;
            }

            fetch(updateOrderUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ order: order })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log("Порядок оновлено!");
                    } else {
                        alert("Помилка при оновленні порядку!");
                    }
                })
                .catch(error => {
                    console.error("Помилка fetch:", error);
                });
        }
    });
});
