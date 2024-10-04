function filterUsers() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('usersTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) { // Пропускаем заголовок
        const tdName = tr[i].getElementsByTagName('td')[0];
        const tdEmail = tr[i].getElementsByTagName('td')[1];
        const tdVacancy = tr[i].getElementsByTagName('td')[2];

        if (tdName || tdEmail || tdVacancy) {
            const txtValueName = tdName.textContent || tdName.innerText;
            const txtValueEmail = tdEmail.textContent || tdEmail.innerText;
            const txtValueVacancy = tdVacancy.textContent || tdVacancy.innerText;

            if (txtValueName.toLowerCase().indexOf(filter) > -1 ||
                txtValueEmail.toLowerCase().indexOf(filter) > -1 ||
                txtValueVacancy.toLowerCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
function filterVacancies() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const table = document.getElementById('vacanciesTable');
    const tr = table.getElementsByTagName('tr');

    for (let i = 1; i < tr.length; i++) { // Пропускаем заголовок
        const tdVacancy = tr[i].getElementsByTagName('td')[0]; // Имя вакансии
        const tdSalary = tr[i].getElementsByTagName('td')[1]; // Зарплата
        const tdAuthor = tr[i].getElementsByTagName('td')[2]; // Автор

        if (tdVacancy || tdSalary || tdAuthor) {
            const txtVacancy = tdVacancy.textContent || tdVacancy.innerText;
            const txtSalary = tdSalary.textContent || tdSalary.innerText;
            const txtAuthor = tdAuthor.textContent || tdAuthor.innerText;

            // Проверяем, соответствует ли хотя бы одно поле
            if (
                txtVacancy.toLowerCase().indexOf(filter) > -1 ||
                txtSalary.toString().toLowerCase().indexOf(filter) > -1 ||
                txtAuthor.toLowerCase().indexOf(filter) > -1
            ) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}


