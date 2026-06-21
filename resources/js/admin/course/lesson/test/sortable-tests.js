import Sortable from 'sortablejs';

document.addEventListener('DOMContentLoaded', function () {
    const sortableElement = document.getElementById('sortable-tests');

    if (!sortableElement) {
        return;
    }

    if (typeof updateOrderUrl === 'undefined' || !updateOrderUrl) {
        console.error('updateOrderUrl is not defined');
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
        console.error('CSRF token was not found');
        return;
    }

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
                        throw new Error(data.message || 'Failed to update test order');
                    }

                    return data;
                })
                .then(data => {
                    if (data.success) {
                        sortableElement.querySelectorAll('li[data-id]').forEach((el, index) => {
                            const strong = el.querySelector('strong');
                            if (strong) {
                                strong.textContent = `#${index + 1}`;
                            }
                        });
                    } else {
                        alert(data.message || 'Failed to update test order.');
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    alert('Could not save the test order.');
                });
        }
    });
});
