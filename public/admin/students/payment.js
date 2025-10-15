function selectMonth(studentId, month, el) {
    const isPaid = el.getAttribute('data-paid') === '1';
    const monthText = new Date(month + '-01').toLocaleString('uk-UA', { year: 'numeric', month: 'long' });

    if (isPaid) {
        if (confirm(`Даний місяць (${monthText}) вже оплачений. Бажаєте скасувати оплату?`)) {
            cancelPayment(studentId, month);
        }
    } else {
        if (confirm(`Оплатити місяць: ${monthText}?`)) {
            const input = document.getElementById('selectedMonthInput' + studentId);
            const form = document.getElementById('paymentForm' + studentId);

            if (input && form) {
                input.value = month;
                form.submit();
            }
        }
    }
}

function cancelPayment(studentId, month) {
    fetch(`/admin/students/${studentId}/subscriptions/${month}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
    })
        .then(response => {
            if (response.ok) {
                alert('Оплата успішно скасована.');
                location.reload();
            } else {
                return response.json().then(data => {
                    throw new Error(data.message || 'Помилка при скасуванні оплати');
                });
            }
        })
        .catch(error => {
            alert(error.message);
        });
}

function setupPaymentToggle(studentId) {
    const subscriptionRadio = document.getElementById(`type-subscription-${studentId}`);
    const singleRadio = document.getElementById(`type-single-${studentId}`);

    const subscriptionDiv = document.getElementById(`subscriptionPayment${studentId}`);
    const singleDiv = document.getElementById(`singlePayment${studentId}`);

    if (!subscriptionRadio || !singleRadio || !subscriptionDiv || !singleDiv) {
        return; // немає потрібних елементів, виходимо
    }

    function toggle() {
        if (subscriptionRadio.checked) {
            subscriptionDiv.style.display = 'block';
            singleDiv.style.display = 'none';
            document.getElementById(`singlePrice${studentId}`).value = '';
        } else {
            subscriptionDiv.style.display = 'none';
            singleDiv.style.display = 'block';
            document.getElementById(`selectedMonthInput${studentId}`).value = '';
        }
    }

    subscriptionRadio.addEventListener('change', toggle);
    singleRadio.addEventListener('change', toggle);

    toggle();
}

function submitSinglePayment(studentId) {
    const priceInput = document.getElementById(`singlePrice${studentId}`);
    const form = document.getElementById(`paymentForm${studentId}`);

    if (!priceInput || !form) return;

    const price = priceInput.value.trim();

    if (!price || isNaN(price) || Number(price) <= 0) {
        alert('Введіть коректну суму поразової оплати.');
        return;
    }

    // Очищаємо місяць, щоб уникнути помилок (поразова оплата не потребує місяця)
    const monthInput = document.getElementById(`selectedMonthInput${studentId}`);
    if (monthInput) {
        monthInput.value = '';
    }

    // Встановлюємо тип оплати явно (необов’язково)
    // Якщо хочеш, можна додати приховане поле з типом оплати, якщо його немає:
    let typeInput = form.querySelector('input[name="type"]');
    if (!typeInput) {
        typeInput = document.createElement('input');
        typeInput.type = 'hidden';
        typeInput.name = 'type';
        form.appendChild(typeInput);
    }
    typeInput.value = 'single';

    form.submit();
}


// Викликати setupPaymentToggle для кожного студента при завантаженні сторінки або відкритті модалки
document.addEventListener('DOMContentLoaded', function() {
    if (window.activeStudentIds && Array.isArray(window.activeStudentIds)) {
        window.activeStudentIds.forEach(studentId => {
            setupPaymentToggle(studentId);
        });
    }
});

