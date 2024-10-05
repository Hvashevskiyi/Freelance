function deleteVacancy(vacancyId) {
    if (confirm('Вы уверены, что хотите удалить эту вакансию?')) {
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
                    // Успешное удаление, перезагрузим страницу
                    window.location.reload();
                } else {
                    alert(data.error || 'Ошибка при удалении вакансии.');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при удалении.');
            });
    }
}
