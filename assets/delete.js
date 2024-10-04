function deleteVacancy(vacancyId) {
    if (confirm("Вы уверены, что хотите удалить эту вакансию?")) {
        fetch('deleteVacancy.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ vacancy_id: vacancyId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Успешное удаление, перезагрузите страницу или удалите строку из таблицы
                    location.reload(); // Перезагрузить страницу
                } else {
                    alert(data.error || 'Произошла ошибка при удалении вакансии.');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
            });
    }
}
