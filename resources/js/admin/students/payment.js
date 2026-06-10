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
            loadSinglePayments(studentId);
        }
    }

    subscriptionRadio.addEventListener('change', toggle);
    singleRadio.addEventListener('change', toggle);

    toggle();
}

function loadSinglePayments(studentId) {
    const monthInput = document.getElementById(`singleMonth${studentId}`);
    const month = monthInput ? monthInput.value : '';
    const url = `/admin/students/${studentId}/single-payments${month ? `?month=${encodeURIComponent(month)}` : ''}`;

    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(response => response.text())
        .then(html => {
            const box = document.getElementById(`singlePaymentsList${studentId}`);
            if (box) {
                box.innerHTML = html;
            }
        })
        .catch(() => {
            const box = document.getElementById(`singlePaymentsList${studentId}`);
            if (box) {
                box.innerHTML = '<div class="text-danger small">Не вдалося завантажити оплати.</div>';
            }
        });
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
    const singleRadio = document.getElementById(`type-single-${studentId}`);
    if (singleRadio) {
        singleRadio.checked = true;
    }

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

document.addEventListener('shown.bs.modal', function (event) {
    const modal = event.target;
    if (!modal.id?.startsWith('paymentModal')) {
        return;
    }

    const studentId = modal.id.replace('paymentModal', '');
    const singleRadio = document.getElementById(`type-single-${studentId}`);

    if (singleRadio?.checked) {
        loadSinglePayments(studentId);
    }
});

window.selectMonth = selectMonth;
window.cancelPayment = cancelPayment;
window.setupPaymentToggle = setupPaymentToggle;
window.loadSinglePayments = loadSinglePayments;
window.submitSinglePayment = submitSinglePayment;
