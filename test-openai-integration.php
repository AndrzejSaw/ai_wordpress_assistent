<?php
/**
 * Тестовый файл для проверки интеграции с OpenAI API
 * Файл: test-openai-integration.php
 * 
 * Этот файл НЕ является частью рабочего плагина!
 * Используйте его только для тестирования в режиме разработки.
 */

// Запретить прямой доступ
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * Тестирование OpenAI API с моделью gpt-4.1-2025-04-14
 * 
 * Функция для проверки подключения к новой модели OpenAI
 */
function ai_assistant_test_openai_model() {
    
    // Получаем API ключ из настроек
    $api_key = get_option('ai_assistant_openai_api_key', '');
    
    if (empty($api_key)) {
        return array(
            'success' => false,
            'message' => 'API ключ OpenAI не настроен'
        );
    }
    
    // Подготавливаем тестовый запрос
    $test_prompt = 'Сгенерируй краткое SEO-описание для статьи о WordPress разработке. Ответь строго в JSON формате с полями: focus_keyword, seo_title, meta_description.';
    
    $url = 'https://api.openai.com/v1/chat/completions';
    
    $data = array(
        'model' => 'gpt-4.1-2025-04-14', // Новейшая модель GPT-4.1
        'messages' => array(
            array(
                'role' => 'system',
                'content' => 'Ты эксперт по SEO-оптимизации. Всегда отвечай строго в формате JSON.'
            ),
            array(
                'role' => 'user', 
                'content' => $test_prompt
            )
        ),
        'max_tokens' => 300,
        'temperature' => 0.3
    );
    
    $args = array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
            'User-Agent' => 'WordPress AI Assistant Plugin Test'
        ),
        'body' => json_encode($data),
        'timeout' => 30,
        'method' => 'POST'
    );
    
    $response = wp_remote_post($url, $args);
    
    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'message' => 'Ошибка соединения: ' . $response->get_error_message()
        );
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code !== 200) {
        return array(
            'success' => false,
            'message' => 'API вернул код: ' . $response_code,
            'body' => $response_body
        );
    }
    
    $response_data = json_decode($response_body, true);
    
    if (!$response_data || !isset($response_data['choices'][0]['message']['content'])) {
        return array(
            'success' => false,
            'message' => 'Некорректный ответ от API'
        );
    }
    
    return array(
        'success' => true,
        'message' => 'Модель gpt-4.1-2025-04-14 работает корректно!',
        'model_used' => $response_data['model'] ?? 'gpt-4.1-2025-04-14',
        'response' => $response_data['choices'][0]['message']['content'],
        'usage' => $response_data['usage'] ?? array()
    );
}

/**
 * Добавляем тестовую функцию в админ-меню (только для разработки)
 */
add_action('admin_menu', function() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        add_submenu_page(
            'tools.php',
            'Test OpenAI Model',
            'Test OpenAI Model',
            'manage_options',
            'test-openai-model',
            'ai_assistant_test_page'
        );
    }
});

/**
 * Страница тестирования
 */
function ai_assistant_test_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('У вас нет прав для доступа к этой странице.'));
    }
    
    $test_result = null;
    
    if (isset($_POST['test_openai']) && wp_verify_nonce($_POST['_wpnonce'], 'test_openai_action')) {
        $test_result = ai_assistant_test_openai_model();
    }
    
    ?>
    <div class="wrap">
        <h1>Тест модели OpenAI GPT-4.1-2025-04-14</h1>
        
        <div class="notice notice-info">
            <p><strong>Внимание:</strong> Эта страница доступна только в режиме разработки (WP_DEBUG = true).</p>
        </div>
        
        <form method="post">
            <?php wp_nonce_field('test_openai_action'); ?>
            <p>
                <input type="submit" name="test_openai" class="button button-primary" value="Тестировать модель GPT-4.1-2025-04-14">
            </p>
        </form>
        
        <?php if ($test_result): ?>
            <div class="notice <?php echo $test_result['success'] ? 'notice-success' : 'notice-error'; ?>">
                <h3>Результат тестирования:</h3>
                <p><strong>Статус:</strong> <?php echo $test_result['message']; ?></p>
                
                <?php if ($test_result['success']): ?>
                    <?php if (isset($test_result['model_used'])): ?>
                        <p><strong>Использованная модель:</strong> <?php echo esc_html($test_result['model_used']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (isset($test_result['response'])): ?>
                        <p><strong>Ответ модели:</strong></p>
                        <pre style="background: #f1f1f1; padding: 10px; border-radius: 4px; overflow-x: auto;"><?php echo esc_html($test_result['response']); ?></pre>
                    <?php endif; ?>
                    
                    <?php if (isset($test_result['usage'])): ?>
                        <p><strong>Использование токенов:</strong></p>
                        <ul>
                            <li>Prompt токены: <?php echo $test_result['usage']['prompt_tokens'] ?? 'N/A'; ?></li>
                            <li>Completion токены: <?php echo $test_result['usage']['completion_tokens'] ?? 'N/A'; ?></li>
                            <li>Всего токенов: <?php echo $test_result['usage']['total_tokens'] ?? 'N/A'; ?></li>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (isset($test_result['body'])): ?>
                        <p><strong>Детали ошибки:</strong></p>
                        <pre style="background: #f1f1f1; padding: 10px; border-radius: 4px; overflow-x: auto;"><?php echo esc_html($test_result['body']); ?></pre>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card" style="margin-top: 20px;">
            <h3>Информация о текущей конфигурации</h3>
            <ul>
                <li><strong>Версия плагина:</strong> <?php echo defined('AI_ASSISTANT_VERSION') ? AI_ASSISTANT_VERSION : 'N/A'; ?></li>
                <li><strong>OpenAI модель:</strong> gpt-4.1-2025-04-14</li>
                <li><strong>API ключ настроен:</strong> <?php echo !empty(get_option('ai_assistant_openai_api_key', '')) ? 'Да' : 'Нет'; ?></li>
                <li><strong>WordPress версия:</strong> <?php echo get_bloginfo('version'); ?></li>
                <li><strong>PHP версия:</strong> <?php echo PHP_VERSION; ?></li>
            </ul>
        </div>
    </div>
    <?php
}
