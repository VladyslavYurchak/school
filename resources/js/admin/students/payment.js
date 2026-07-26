function selectMonth(studentId, month, el) {
    const isPaid = el.getAttribute('data-paid') === '1';
    const monthText = new Date(month + '-01').toLocaleString('uk-UA', { year: 'numeric', month: 'long' });

    if (isPaid) {
        showSubscriptionActions(studentId, month, monthText);
    } else {
        hideSubscriptionActions(studentId);

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

function showSubscriptionActions(studentId, month, monthText) {
    const actions = document.getElementById(`subscriptionActions${studentId}`);
    const title = document.getElementById(`subscriptionActionsTitle${studentId}`);
    const target = document.getElementById(`subscriptionMoveTarget${studentId}`);

    if (!actions || !title || !target) {
        return;
    }

    actions.dataset.sourceMonth = month;
    title.textContent = `Оплачений місяць: ${monthText}`;

    const nextMonth = new Date(month + '-01');
    nextMonth.setMonth(nextMonth.getMonth() + 1);
    target.value = `${nextMonth.getFullYear()}-${String(nextMonth.getMonth() + 1).padStart(2, '0')}`;

    actions.classList.remove('d-none');
    actions.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function hideSubscriptionActions(studentId) {
    const actions = document.getElementById(`subscriptionActions${studentId}`);

    if (actions) {
        actions.classList.add('d-none');
        delete actions.dataset.sourceMonth;
    }
}

function moveSubscription(studentId) {
    const actions = document.getElementById(`subscriptionActions${studentId}`);
    const target = document.getElementById(`subscriptionMoveTarget${studentId}`);
    const sourceMonth = actions?.dataset.sourceMonth;
    const targetMonth = target?.value;

    if (!sourceMonth || !targetMonth) {
        alert('Виберіть місяць, на який потрібно перенести абонемент.');
        return;
    }

    if (!confirm('Перенести абонемент і пов’язаний платіж на вибраний місяць?')) {
        return;
    }

    fetch(`/admin/students/${studentId}/subscriptions/${sourceMonth}/move`, {
        method: 'PUT',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ target_month: targetMonth }),
    })
        .then(handlePaymentResponse)
        .then(data => {
            alert(data.message);
            location.reload();
        })
        .catch(error => alert(error.message));
}

function cancelPayment(studentId) {
    const actions = document.getElementById(`subscriptionActions${studentId}`);
    const month = actions?.dataset.sourceMonth;

    if (!month) {
        return;
    }

    if (!confirm('Підтверджуєте, що кошти вже повернено вручну? Після проведеного або зарахованого заняття скасування буде заборонено.')) {
        return;
    }

    fetch(`/admin/students/${studentId}/subscriptions/${month}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
    })
        .then(handlePaymentResponse)
        .then(data => {
            alert(data.message);
            location.reload();
        })
        .catch(error => alert(error.message));
}

function handlePaymentResponse(response) {
    return response.json().catch(() => ({})).then(data => {
        if (!response.ok) {
            throw new Error(data.message || 'Не вдалося виконати дію з абонементом.');
        }

        return data;
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
window.moveSubscription = moveSubscription;
window.setupPaymentToggle = setupPaymentToggle;
window.loadSinglePayments = loadSinglePayments;
window.submitSinglePayment = submitSinglePayment;
