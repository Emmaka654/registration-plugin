jQuery(document).ready(function ($) {
    $('#button-authorization').on('click', function (e) {
        e.preventDefault();
        const authorizationForm = document.querySelector('#authorization-container');
        authorizationForm.style.display = authorizationForm.style.display === 'block' ? 'none' : 'block';
    });
});

jQuery(document).ready(function ($) {
    $('#authorization-form').on('submit', function () {
        const messageBlock = $('#authorization-response-message');
        const formData = $(this).serialize();

        $.ajax({
            url: ajax_object.ajax_url,
            method: 'POST',
            data: formData + '&action=custom_authorization',
            success: function (response) {
                messageBlock.empty();
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }
                if (response.success) {
                    messageBlock.append('<div class="success">' + response.message + '</div>');
                } else {
                    messageBlock.append('<div class="error">' + response.message + '</div>');
                }
            },
            error: function () {
                messageBlock.append('<div class="error">Произошла ошибка. Попробуйте еще раз.</div>');
            }
        });
    });
});
