<?php
/*
Plugin Name: Custom Registration
Description: User registration/authorization.
Version: 1.0
Author: Emma
*/

//Регистрация
function enqueue_registration_script()
{
    wp_enqueue_script('registration-script', get_template_directory_uri() . '/../../plugins/registration-plugin/registration-processing.js', array('jquery'), null, true);
}

add_action('wp_enqueue_scripts', 'enqueue_registration_script');

function registration_form($username, $password, $email, $fname)
{
//    $_SERVER['REQUEST_URI'] возвращает URI текущей страницы, что означает, что данные формы будут отправлены на ту же страницу, на которой находится форма.
//    <label> — это HTML-элемент, который используется для создания метки для элемента формы
    echo ' 
<form id="registration-form" action="' . $_SERVER['REQUEST_URI'] . '" method="post"> 
    <div> 
        <label for="username">Имя пользователя <strong>*</strong></label> 
        <input type="text" name="username" value="' . (isset($_POST['username']) ? $username : null) . '"> 
    </div> 

    <div> 
        <label for="password">Пароль <strong>*</strong></label> 
        <input type="password" name="password" value="' . (isset($_POST['password']) ? $password : null) . '"> 
    </div> 

    <div> 
        <label for="email">Email <strong>*</strong></label> 
        <input type="text" name="email" value="' . (isset($_POST['email']) ? $email : null) . '"> 
    </div> 

    <div> 
        <label for="firstname">ФИО</label> 
        <input type="text" name="fname" value="' . (isset($_POST['fname']) ? $fname : null) . '"> 
    </div> 

    <input id ="submit-registration" type="submit" name="submit" value="Зарегистрироваться"/> 
</form> 
<div id="response-message"></div> <!-- Блок для вывода сообщений -->
';
}

function registration_validation($username, $password, $email, $fname)
{
    $reg_errors = new WP_Error;

    if (empty($username) || empty($password) || empty($email)) {
        $reg_errors->add('field', 'Required form field is missing');
    }

    if (4 > strlen($username)) {
        $reg_errors->add('username_length', 'Username too short. At least 4 characters is required');
    }

    if (username_exists($username))
        $reg_errors->add('user_name', 'Sorry, that username already exists!');

    if (!validate_username($username)) {
        $reg_errors->add('username_invalid', 'Sorry, the username you entered is not valid');
    }

    if (5 > strlen($password)) {
        $reg_errors->add('password', 'Password length must be greater than 5');
    }

    if (!is_email($email)) {
        $reg_errors->add('email_invalid', 'Email is not valid');
    }

    if (email_exists($email)) {
        $reg_errors->add('email', 'Email Already in use');
    }

    // Если есть ошибки, возвращаем их в виде массива
    //  Метод get_error_messages() возвращает массив всех сообщений об ошибках, которые были добавлены к объекту WP_Error
    if (is_wp_error($reg_errors) && !empty($reg_errors->get_error_messages())) {
        return $reg_errors->get_error_messages();
    }

    return []; // Нет ошибок
}

function complete_registration($username, $password, $email, $fname)
{
    $userdata = array(
        'user_login' => $username,
        'user_email' => $email,
        'user_pass' => $password,
    );
    $user_id = wp_insert_user($userdata);

    // Сохранение дополнительного поля
    update_user_meta($user_id, 'fname', $fname);
}

function custom_registration_function()
{
    // Проверяем, был ли отправлен AJAX-запрос
    if (isset($_POST['username'])) {

        // Санитизация данных
        // Функция sanitize_user() очищает это значение, удаляя недопустимые символы и обеспечивая, что оно соответствует требованиям для имени пользователя.
        // Функция esc_attr() используется для экранирования строки, чтобы предотвратить возможные атаки
        $username = sanitize_user($_POST['username']);
        $password = esc_attr($_POST['password']);
        $email = sanitize_email($_POST['email']);
        $fname = sanitize_text_field($_POST['fname']);

        // Валидация данных
        $validation_errors = registration_validation($username, $password, $email, $fname);

        // Если есть ошибки валидации, возвращаем их в ответе
        if (!empty($validation_errors)) {
            echo json_encode([
                'success' => false,
                'messages' => $validation_errors // Список ошибок
            ]);
            exit(); // Завершаем выполнение скрипта
        }

        // Завершение регистрации
        complete_registration($username, $password, $email, $fname);

        // Отправляем успешный ответ
        echo json_encode([
            'success' => true,
            'message' => 'Регистрация прошла успешно! Теперь вы можете выполнить вход.'
        ]);
        exit(); // Завершаем выполнение скрипта
    } else {
        // Если данные не были отправлены, возвращаем форму
        registration_form('', '', '', '');
    }
}

// Регистрация AJAX действий
add_action('wp_ajax_custom_registration', 'custom_registration_function');
add_action('wp_ajax_nopriv_custom_registration', 'custom_registration_function');

//Авторизация
function enqueue_authorization_script()
{
    wp_enqueue_script('authorization-script', get_template_directory_uri() . '/../../plugins/registration-plugin/authorization-processing.js', array('jquery'), null, true);
}

add_action('wp_enqueue_scripts', 'enqueue_authorization_script');

function authorization_form($username, $password)
{
    echo '
<form id="authorization-form" action="' . $_SERVER['REQUEST_URI'] . '" method="post">
    <div>
        <label for="username">Введите имя пользователя</label>
        <input type="text" name="authorization-username" value="' . (isset($_POST['username']) ? $username : null) . '">
    </div>

    <div>
        <label for="password">Введите пароль</label>
        <input type="password" name="authorization-password" value="' . (isset($_POST['password']) ? $password : null) . '">
    </div>

    <input id ="submit-authorization" type="submit" name="submit" value="Вход"/>
</form>
<div id="authorization-response-message"></div>
';
}

function custom_authorization_function()
{
    if (isset($_POST['authorization-username']) && isset($_POST['authorization-password'])) {
        $username = sanitize_user($_POST['authorization-username']);
        $password = esc_attr($_POST['authorization-password']);

        $user = wp_authenticate($username, $password);
        if (is_wp_error($user)) {
            echo json_encode([
                'success' => false,
                'message' => 'Неверные логин или пароль'
            ]);
            exit();
        }

        //устанавливаем текущего пользователя в глобальной переменной $current_user
        wp_set_current_user($user->ID);
        //устанавливаем аутентификационный куки для пользователя
        wp_set_auth_cookie($user->ID);

        echo json_encode([
            'success' => true,
            'message' => 'Авторизация прошла успешно!'
        ]);
        exit();
    } else {
        authorization_form('', '', '', '');
    }
}

add_action('wp_ajax_custom_authorization', 'custom_authorization_function');
add_action('wp_ajax_nopriv_custom_authorization', 'custom_authorization_function');

//Проверка, авторизован ли пользователь
function custom_auth_buttons()
{
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        return '<div>Привет, ' . esc_html($user->display_name) . '</div>';
    } else {
        return '
            <button id="button-authorization" type="button">Вход</button>
            <button id="button-registration" type="button">Регистрация</button>';
    }
}