document.addEventListener('DOMContentLoaded', function () {
    const deleteButtons = document.querySelectorAll('.delete-vacancy');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const vacancyId = this.getAttribute('data-id');

            // Подтверждение удаления
            if (confirm('Вы уверены, что хотите удалить эту вакансию?')) {
                deleteVacancy(vacancyId);
            }
        });
    });
});

function deleteVacancy(vacancyId) {
    fetch('deleteVacancy.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ vacancy_id: vacancyId })
    })
        .then(response => response.json())
        .then(data => {
            const deleteErrorDiv = document.getElementById('deleteError');
            if (data.success) {
                // Успешное удаление, перезагружаем страницу
                location.reload();
            } else {
                // Ошибка удаления
                deleteErrorDiv.innerText = data.error;
            }
        })
        .catch((error) => {
            console.error('Ошибка:', error);
            document.getElementById('deleteError').innerText = 'Произошла ошибка при удалении вакансии.';
        });
}
