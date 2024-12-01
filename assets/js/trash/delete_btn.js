document.addEventListener('DOMContentLoaded', function () {
    const deleteButtons = document.querySelectorAll('button[type="submit"]');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            const confirmation = confirm("Вы уверены, что хотите удалить этого пользователя?");
            if (!confirmation) {
                event.preventDefault(); // Отменить действие, если пользователь не подтвердил
            }
        });
    });
});ььььььь



