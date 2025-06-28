<?php
/**
 * Дополнительные хуки и фильтры для AI Assistant
 * 
 * @package AI_Assistant
 * @version 1.0.0
 */

// Запретить прямой доступ к файлу
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Фильтр для добавления кастомных полей в контекст генерации
 * 
 * @param array $custom_fields Существующие кастомные поля
 * @param int $post_id ID записи
 * @param string $post_type Тип записи
 * @return array Обновленные кастомные поля
 */
function ai_assistant_custom_fields_filter($custom_fields, $post_id, $post_type) {
    // Пример использования:
    // add_filter('ai_assistant_custom_fields', 'my_custom_fields_handler', 10, 3);
    
    return apply_filters('ai_assistant_custom_fields', $custom_fields, $post_id, $post_type);
}

/**
 * Фильтр для модификации промпта перед отправкой в OpenAI
 * 
 * @param string $prompt Исходный промпт
 * @param array $context Контекст записи
 * @param string $post_type Тип записи
 * @return string Модифицированный промпт
 */
function ai_assistant_prompt_filter($prompt, $context, $post_type) {
    return apply_filters('ai_assistant_modify_prompt', $prompt, $context, $post_type);
}

/**
 * Действие после успешной генерации SEO-данных
 * 
 * @param int $post_id ID записи
 * @param array $seo_data Сгенерированные SEO-данные
 * @param string $post_type Тип записи
 */
function ai_assistant_after_generation($post_id, $seo_data, $post_type) {
    do_action('ai_assistant_seo_generated', $post_id, $seo_data, $post_type);
}

/**
 * Фильтр для настройки параметров OpenAI API
 * 
 * @param array $api_params Параметры API
 * @param string $post_type Тип записи
 * @return array Модифицированные параметры
 */
function ai_assistant_api_params_filter($api_params, $post_type) {
    return apply_filters('ai_assistant_api_params', $api_params, $post_type);
}

/**
 * Фильтр для кастомизации мета-полей Yoast SEO для сохранения
 * 
 * @param array $meta_fields Поля для сохранения
 * @param int $post_id ID записи
 * @return array Модифицированные поля
 */
function ai_assistant_meta_fields_filter($meta_fields, $post_id) {
    return apply_filters('ai_assistant_meta_fields', $meta_fields, $post_id);
}

// Примеры использования хуков:

/**
 * Пример добавления кастомных полей для специального типа записи
 */
// add_filter('ai_assistant_custom_fields', function($custom_fields, $post_id, $post_type) {
//     if ($post_type === 'my_custom_type') {
//         $custom_fields['My Custom Field'] = get_post_meta($post_id, 'my_field', true);
//     }
//     return $custom_fields;
// }, 10, 3);

/**
 * Пример модификации промпта для конкретного типа записи
 */
// add_filter('ai_assistant_modify_prompt', function($prompt, $context, $post_type) {
//     if ($post_type === 'event') {
//         $prompt .= "\nДополнительно учти, что это мероприятие и добавь временные маркеры в SEO.";
//     }
//     return $prompt;
// }, 10, 3);

/**
 * Пример действия после генерации SEO
 */
// add_action('ai_assistant_seo_generated', function($post_id, $seo_data, $post_type) {
//     // Логирование или уведомления
//     error_log("SEO generated for {$post_type} #{$post_id}");
// }, 10, 3);
