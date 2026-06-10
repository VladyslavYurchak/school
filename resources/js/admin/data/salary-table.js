document.addEventListener("DOMContentLoaded", function () {
    const salaryModal = document.getElementById("teachersSalaryModal");

    salaryModal?.addEventListener("shown.bs.modal", function () {
        loadTeachersSalary();
    });

    document.getElementById("salary-month")?.addEventListener("change", function () {
        loadTeachersSalary();
    });

    function loadTeachersSalary() {
        const month = document.getElementById("salary-month")?.value;
        const tableContainer = document.getElementById("teachers-salary-table");

        tableContainer.innerHTML = "<p>Завантаження...</p>";

        fetch(`/admin/data/teachers-salary?month=${month}`)
            .then(res => {
                if (!res.ok) throw new Error("Помилка при завантаженні");
                return res.json();
            })
            .then(data => {
                if (!data.teachers.length) {
                    tableContainer.innerHTML = "<p>Дані відсутні.</p>";
                    return;
                }

                let html = `
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Прізвище</th>
                                <th>Ім'я</th>
                                <th>Індивідуальні заняття</th>
                                <th>Групові заняття</th>
                                <th>Зарплата, грн</th>
                            </tr>
                        </thead>
                        <tbody>`;

                data.teachers.forEach(t => {
                    html += `
                        <tr>
                            <td>${t.last_name}</td>
                            <td>${t.first_name}</td>
                            <td>${t.individualCount}</td>
                            <td>${t.groupCount}</td>
                            <td>${Number(t.salary).toLocaleString('uk-UA', { minimumFractionDigits: 2 })}</td>
                        </tr>`;
                });

                html += `</tbody></table>`;
                tableContainer.innerHTML = html;
            })
            .catch(() => {
                tableContainer.innerHTML = "<p class='text-danger'>Помилка при завантаженні даних.</p>";
            });
    }
});
