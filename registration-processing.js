jQuery(document).ready(function ($) {
    $('#button-registration').on('click', function (e) {
        const registrationForm = document.querySelector('#registration-container');

        // Показать или скрыть всплывающее окно при нажатии на кнопку регистрации
        registrationForm.style.display = registrationForm.style.display === 'block' ? 'none' : 'block';
    });
});

jQuery(document).ready(function ($) {
    $('#registration-form').on('submit', function (e) {
        e.preventDefault();
        const messageBlock = $('#response-message');
        // Формирует строку, которая может быть отправлена на сервер
        const formData = $(this).serialize();

        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: formData + '&action=custom_registration',
            success: function (response) {
                messageBlock.empty(); // Очищаем предыдущие сообщения
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                if (response.success) {
                    messageBlock.append('<div class="success">' + response.message + '</div>');
                } else {
                    if (Array.isArray(response.messages)) {
                        response.messages.forEach(function (message) {
                            messageBlock.append('<div class="error">' + message + '</div>');
                        });
                    } else {
                        messageBlock.append('<div class="error">' + response.messages + '</div>');
                    }
                }
            },
            error: function () {
                messageBlock.append('<div class="error">Произошла ошибка. Попробуйте еще раз.</div>');
            }
        });
    });
});