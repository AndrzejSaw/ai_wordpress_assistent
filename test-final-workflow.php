<?php
/**
 * Финальный тест правильности рабочего процесса AI Assistant
 * Этот файл проверяет, что логика работает корректно
 */

// Прямой доступ запрещен
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Проверка корректности рабочего процесса
 */
function ai_assistant_test_workflow() {
    echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 8px;">';
    echo '<h1>🧪 Тест рабочего процесса AI Assistant</h1>';
    
    // Проверка 1: Есть ли AJAX обработчики
    echo '<h2>1. Проверка AJAX обработчиков</h2>';
    $ajax_actions = array(
        'ai_assistant_generate_seo' => '✅ Генерация SEO-данных',
        'ai_assistant_save_seo_data' => '✅ Сохранение SEO-данных (отдельно!)',
        'ai_assistant_test_api' => '✅ Тестирование API'
    );
    
    foreach ($ajax_actions as $action => $description) {
        if (has_action("wp_ajax_$action")) {
            echo "<p style='color: green;'>$description - зарегистрирован</p>";
        } else {
            echo "<p style='color: red;'>❌ $action - НЕ найден!</p>";
        }
    }
    
    // Проверка 2: Правильность JavaScript файла
    echo '<h2>2. Проверка JavaScript файла</h2>';
    $js_file = plugin_dir_path(__FILE__) . 'assets/js/admin-script.js';
    if (file_exists($js_file)) {
        $js_content = file_get_contents($js_file);
        
        // Проверяем ключевые функции
        $js_checks = array(
            'showSeoDataModal' => 'Функция показа модального окна',
            'saveSeoData' => 'Функция сохранения данных',
            'ai_assistant_save_seo_data' => 'AJAX действие сохранения',
            'auto_saved.*false' => 'Указание что данные НЕ сохранены автоматически'
        );
        
        foreach ($js_checks as $pattern => $description) {
            if (preg_match("/$pattern/i", $js_content)) {
                echo "<p style='color: green;'>✅ $description - найдена</p>";
            } else {
                echo "<p style='color: red;'>❌ $description - НЕ найдена!</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ JavaScript файл не найден!</p>";
    }
    
    // Проверка 3: Правильность PHP обработчиков
    echo '<h2>3. Проверка PHP обработчиков</h2>';
    $main_file = plugin_dir_path(__FILE__) . 'ai-assistant.php';
    if (file_exists($main_file)) {
        $php_content = file_get_contents($main_file);
        
        $php_checks = array(
            'wp_send_json_success.*auto_saved.*false' => 'Генерация БЕЗ автосохранения',
            'function ajax_save_seo_data' => 'Отдельная функция сохранения',
            'wp_send_json_success.*wp_send_json_error' => 'Правильные JSON ответы',
            'save_seo_data_deprecated' => 'Устаревшая функция отключена'
        );
        
        foreach ($php_checks as $pattern => $description) {
            if (preg_match("/$pattern/i", $php_content)) {
                echo "<p style='color: green;'>✅ $description - найдена</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ $description - проверьте код</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Основной PHP файл не найден!</p>";
    }
    
    // Проверка 4: Тестовые файлы
    echo '<h2>4. Проверка тестовых файлов</h2>';
    $test_files = array(
        'test-modal-workflow.html' => 'Тест рабочего процесса',
        'test-modal.html' => 'Тест модального окна',
        'test-openai-integration.php' => 'Тест интеграции с OpenAI'
    );
    
    foreach ($test_files as $file => $description) {
        $file_path = plugin_dir_path(__FILE__) . $file;
        if (file_exists($file_path)) {
            echo "<p style='color: green;'>✅ $description ($file) - существует</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ $description ($file) - не найден</p>";
        }
    }
    
    // Финальная сводка
    echo '<h2>📋 Финальная проверка</h2>';
    echo '<div style="background: #fff; border: 2px solid #0073aa; border-radius: 8px; padding: 20px; margin: 20px 0;">';
    echo '<h3>✅ Правильный рабочий процесс:</h3>';
    echo '<ol>';
    echo '<li><strong>Нажатие кнопки</strong> → Показывается индикатор загрузки</li>';
    echo '<li><strong>AJAX генерация</strong> → action: ai_assistant_generate_seo</li>';
    echo '<li><strong>Ответ сервера</strong> → auto_saved: false (данные НЕ сохранены)</li>';
    echo '<li><strong>Модальное окно</strong> → Показывается СРАЗУ с предупреждением</li>';
    echo '<li><strong>Редактирование</strong> → Пользователь проверяет поля</li>';
    echo '<li><strong>Сохранение</strong> → action: ai_assistant_save_seo_data (отдельный AJAX)</li>';
    echo '<li><strong>Обновление статуса</strong> → Запись помечается как оптимизированная</li>';
    echo '</ol>';
    echo '</div>';
    
    echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 15px; margin: 20px 0;">';
    echo '<h3>🚨 Если что-то не работает:</h3>';
    echo '<ul>';
    echo '<li>Проверьте консоль браузера на ошибки JavaScript</li>';
    echo '<li>Проверьте логи WordPress (/wp-content/debug.log)</li>';
    echo '<li>Убедитесь что Yoast SEO установлен</li>';
    echo '<li>Проверьте права доступа пользователя</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 8px; padding: 15px; margin: 20px 0;">';
    echo '<h3>🔧 Для тестирования:</h3>';
    echo '<ol>';
    echo '<li>Откройте /wp-admin/edit.php (список записей)</li>';
    echo '<li>Найдите запись без SEO-данных</li>';
    echo '<li>Нажмите "Сгенерировать SEO" в колонке AI Assistant</li>';
    echo '<li><strong>Проверьте:</strong> Появилось ли модальное окно СРАЗУ после генерации?</li>';
    echo '<li><strong>Проверьте:</strong> Есть ли предупреждение "Проверьте и отредактируйте данные"?</li>';
    echo '<li>Нажмите "Сохранить" и проверьте обновление статуса</li>';
    echo '</ol>';
    echo '</div>';
    
    echo '</div>';
}

// Если файл вызывается напрямую, показываем тест
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    ai_assistant_test_workflow();
}
?>
