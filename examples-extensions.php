<?php
/**
 * Примеры использования системы расширения AI Assistant
 * Файл: examples-extensions.php
 * 
 * ВНИМАНИЕ: Этот файл НЕ является частью основного плагина!
 * Он содержит примеры того, как можно расширить функционал AI Assistant
 * с помощью хуков и фильтров.
 */

// Запретить прямой доступ
if (!defined('ABSPATH')) {
    exit('Direct access denied.');
}

/**
 * ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ ФИЛЬТРОВ
 */

/**
 * Пример 1: Добавление дополнительного контекста для определенного типа записи
 */
add_filter('ai_assistant_post_context', 'add_custom_context_for_events', 10, 3);
function add_custom_context_for_events($context, $post, $post_type) {
    
    // Если это кастомный тип записи "events"
    if ($post_type === 'events') {
        
        // Добавляем информацию о дате события
        $event_date = get_post_meta($post->ID, 'event_date', true);
        if ($event_date) {
            $context['event_date'] = date('d.m.Y', strtotime($event_date));
        }
        
        // Добавляем информацию о месте проведения
        $event_location = get_post_meta($post->ID, 'event_location', true);
        if ($event_location) {
            $context['event_location'] = $event_location;
        }
        
        // Добавляем информацию о категории события
        $event_categories = wp_get_post_terms($post->ID, 'event_category');
        if (!empty($event_categories)) {
            $context['event_categories'] = wp_list_pluck($event_categories, 'name');
        }
    }
    
    return $context;
}

/**
 * Пример 2: Модификация промпта для лучшей генерации
 */
add_filter('ai_assistant_prompt', 'customize_prompt_for_products', 10, 3);
function customize_prompt_for_products($prompt, $context, $post_type) {
    
    // Специальный промпт для товаров
    if ($post_type === 'product') {
        $custom_prompt = "Ты эксперт по e-commerce SEO. Создай SEO-данные для товара с учетом коммерческой направленности.

КОНТЕКСТ ТОВАРА:
Название: {$context['title']}
Описание: {$context['content']}";

        // Добавляем информацию о цене если есть
        if (isset($context['price'])) {
            $custom_prompt .= "\nЦена: {$context['price']}";
        }

        // Добавляем информацию о категории
        if (isset($context['categories'])) {
            $custom_prompt .= "\nКатегории: " . implode(', ', $context['categories']);
        }

        $custom_prompt .= "

ТРЕБОВАНИЯ:
1. Фокусное ключевое слово должно быть коммерческим (включать слова: купить, заказать, цена и т.д.)
2. SEO-заголовок должен содержать призыв к действию
3. Мета-описание должно мотивировать к покупке
4. Учитывай конкурентное окружение в e-commerce

Ответь строго в JSON формате:
{
    \"focus_keyword\": \"ключевое слово\",
    \"seo_title\": \"SEO заголовок (до 60 символов)\",
    \"meta_description\": \"Мета описание (до 160 символов)\"
}";

        return $custom_prompt;
    }
    
    return $prompt;
}

/**
 * Пример 3: Постобработка сгенерированных данных
 */
add_filter('ai_assistant_generated_data', 'postprocess_seo_data', 10, 3);
function postprocess_seo_data($data, $context, $post_type) {
    
    // Добавляем префикс к заголовкам для определенного типа записи
    if ($post_type === 'news') {
        $data['seo_title'] = '🔥 ' . $data['seo_title'];
    }
    
    // Добавляем суффикс к мета-описаниям
    if ($post_type === 'service') {
        $data['meta_description'] .= ' | Закажите услугу прямо сейчас!';
    }
    
    // Проверяем длину и обрезаем при необходимости
    if (strlen($data['seo_title']) > 60) {
        $data['seo_title'] = substr($data['seo_title'], 0, 57) . '...';
    }
    
    if (strlen($data['meta_description']) > 160) {
        $data['meta_description'] = substr($data['meta_description'], 0, 157) . '...';
    }
    
    return $data;
}

/**
 * Пример 4: Настройка параметров OpenAI
 */
add_filter('ai_assistant_openai_parameters', 'customize_openai_params', 10, 2);
function customize_openai_params($params, $post_type) {
    
    // Для творческих типов записей увеличиваем creativity
    if (in_array($post_type, ['art', 'portfolio', 'gallery'])) {
        $params['temperature'] = 0.7; // Больше креативности
        $params['max_tokens'] = 800;   // Больше токенов
    }
    
    // Для технических типов записей уменьшаем creativity
    if (in_array($post_type, ['documentation', 'manual', 'guide'])) {
        $params['temperature'] = 0.1; // Меньше креативности
        $params['top_p'] = 0.8;       // Более фокусированные ответы
    }
    
    return $params;
}

/**
 * ПРИМЕРЫ ИСПОЛЬЗОВАНИЯ ДЕЙСТВИЙ (HOOKS)
 */

/**
 * Пример 5: Логирование всех генераций
 */
add_action('ai_assistant_before_generation', 'log_generation_start', 10, 2);
function log_generation_start($post_id, $post_type) {
    error_log("AI Assistant: Starting generation for post {$post_id} of type {$post_type}");
}

add_action('ai_assistant_after_generation', 'log_generation_success', 10, 3);
function log_generation_success($post_id, $post_type, $generated_data) {
    error_log("AI Assistant: Successfully generated SEO data for post {$post_id}");
    
    // Можно отправить уведомление администратору
    $admin_email = get_option('admin_email');
    $subject = 'AI Assistant: SEO данные сгенерированы';
    $message = "SEO данные успешно сгенерированы для записи #{$post_id} ({$post_type})";
    
    wp_mail($admin_email, $subject, $message);
}

/**
 * Пример 6: Обработка ошибок генерации
 */
add_action('ai_assistant_generation_error', 'handle_generation_error', 10, 3);
function handle_generation_error($post_id, $error_message, $context) {
    
    // Логируем ошибку
    error_log("AI Assistant Error for post {$post_id}: {$error_message}");
    
    // Сохраняем ошибку в мета-поле для последующего анализа
    update_post_meta($post_id, '_ai_assistant_last_error', array(
        'error' => $error_message,
        'timestamp' => current_time('mysql'),
        'context' => $context
    ));
    
    // Увеличиваем счетчик ошибок
    $error_count = get_option('ai_assistant_error_count', 0);
    update_option('ai_assistant_error_count', $error_count + 1);
}

/**
 * Пример 7: Интеграция со сторонними плагинами
 */
add_action('ai_assistant_after_generation', 'integrate_with_analytics', 10, 3);
function integrate_with_analytics($post_id, $post_type, $generated_data) {
    
    // Интеграция с Google Analytics (если используется плагин)
    if (function_exists('ga_send_event')) {
        ga_send_event('AI Assistant', 'SEO Generated', $post_type);
    }
    
    // Интеграция с плагином статистики
    if (function_exists('custom_stats_track_event')) {
        custom_stats_track_event('ai_seo_generation', array(
            'post_id' => $post_id,
            'post_type' => $post_type,
            'focus_keyword' => $generated_data['focus_keyword']
        ));
    }
}

/**
 * Пример 8: Автоматическая публикация в соцсети после генерации
 */
add_action('ai_assistant_after_generation', 'auto_social_share', 10, 3);
function auto_social_share($post_id, $post_type, $generated_data) {
    
    // Публикуем только определенные типы записей
    if (!in_array($post_type, ['post', 'news', 'announcement'])) {
        return;
    }
    
    $post = get_post($post_id);
    
    // Формируем сообщение для соцсети
    $social_message = "Новая оптимизированная статья: {$post->post_title}";
    $social_message .= "\n\n{$generated_data['meta_description']}";
    $social_message .= "\n\n" . get_permalink($post_id);
    
    // Отправляем в Telegram (пример)
    if (function_exists('send_telegram_message')) {
        send_telegram_message($social_message);
    }
    
    // Отправляем в Twitter (пример)
    if (function_exists('post_to_twitter')) {
        post_to_twitter($social_message);
    }
}

/**
 * Пример 9: Создание задач для планировщика
 */
add_action('ai_assistant_after_generation', 'schedule_seo_review', 10, 3);
function schedule_seo_review($post_id, $post_type, $generated_data) {
    
    // Планируем проверку SEO через месяц
    wp_schedule_single_event(
        strtotime('+1 month'),
        'ai_assistant_seo_review',
        array($post_id)
    );
}

// Обработчик запланированной проверки
add_action('ai_assistant_seo_review', 'perform_seo_review');
function perform_seo_review($post_id) {
    
    // Проверяем актуальность SEO данных
    $last_generation = get_post_meta($post_id, '_ai_assistant_generated_at', true);
    
    if ($last_generation && strtotime($last_generation) < strtotime('-1 month')) {
        
        // Отправляем уведомление о необходимости обновления
        $admin_email = get_option('admin_email');
        $post_title = get_the_title($post_id);
        
        wp_mail(
            $admin_email,
            'AI Assistant: Требуется обновление SEO',
            "Записи \"{$post_title}\" (ID: {$post_id}) требуется обновление SEO-данных."
        );
    }
}

/**
 * ПРИМЕРЫ РАБОТЫ С ТАКСОНОМИЯМИ (НОВОЕ!)
 */

/**
 * Пример 1: Исключение определенных таксономий из контекста
 */
add_filter('ai_assistant_excluded_taxonomies', 'exclude_service_taxonomies', 10, 3);
function exclude_service_taxonomies($excluded, $post_type, $post_id) {
    // Для товаров исключаем служебные таксономии WooCommerce
    if ($post_type === 'product') {
        $excluded[] = 'product_visibility';
        $excluded[] = 'product_shipping_class';
        $excluded[] = 'pa_internal_notes'; // Кастомный атрибут для внутренних заметок
    }
    
    // Для событий исключаем техническую таксономию
    if ($post_type === 'event') {
        $excluded[] = 'event_internal_status';
    }
    
    return $excluded;
}

/**
 * Пример 2: Кастомные названия таксономий для лучшего понимания AI
 */
add_filter('ai_assistant_taxonomy_translations', 'add_custom_taxonomy_translations', 10, 3);
function add_custom_taxonomy_translations($translations, $taxonomy_slug, $taxonomy_object) {
    // Добавляем переводы для кастомных таксономий проекта
    $custom_translations = array(
        'course_level' => 'Уровень сложности курса',
        'course_duration' => 'Продолжительность обучения',
        'tutorial_difficulty' => 'Сложность туториала',
        'project_status' => 'Статус проекта',
        'client_industry' => 'Отрасль клиента',
        'case_study_type' => 'Тип кейса',
        'technology_stack' => 'Технологический стек',
        'team_size' => 'Размер команды',
        'budget_range' => 'Диапазон бюджета'
    );
    
    return array_merge($translations, $custom_translations);
}

/**
 * Пример 3: Модификация отображаемого названия таксономии в зависимости от контекста
 */
add_filter('ai_assistant_taxonomy_display_name', 'customize_taxonomy_display_name', 10, 4);
function customize_taxonomy_display_name($display_name, $taxonomy_slug, $taxonomy_object, $post_type) {
    // Для образовательного контента делаем названия более описательными
    if ($post_type === 'course') {
        switch ($taxonomy_slug) {
            case 'course_category':
                return 'Направление обучения';
            case 'course_level':
                return 'Целевая аудитория по уровню';
            case 'course_format':
                return 'Формат проведения занятий';
        }
    }
    
    // Для портфолио подчеркиваем профессиональный аспект
    if ($post_type === 'portfolio') {
        switch ($taxonomy_slug) {
            case 'portfolio_category':
                return 'Сфера экспертизы';
            case 'project_type':
                return 'Тип выполненного проекта';
            case 'client_size':
                return 'Масштаб клиента';
        }
    }
    
    return $display_name;
}

/**
 * Пример 4: Добавление дополнительного контекста на основе таксономий
 */
add_filter('ai_assistant_taxonomies_context', 'enhance_taxonomies_context', 10, 3);
function enhance_taxonomies_context($context, $post_id, $post_type) {
    // Для товаров добавляем информацию о брендах и характеристиках
    if ($post_type === 'product') {
        // Получаем бренд товара
        $brands = wp_get_post_terms($post_id, 'product_brand', array('fields' => 'names'));
        if (!empty($brands)) {
            $context['Бренд'] = implode(', ', $brands);
        }
        
        // Добавляем информацию о сезонности
        $seasons = wp_get_post_terms($post_id, 'product_season', array('fields' => 'names'));
        if (!empty($seasons)) {
            $context['Сезонность'] = implode(', ', $seasons);
        }
        
        // Подсчитываем общее количество категорий для контекста
        $all_categories = wp_get_post_terms($post_id, 'product_cat');
        if (count($all_categories) > 3) {
            $context['Дополнительная информация'] = 'Товар относится к ' . count($all_categories) . ' категориям - широкий ассортимент';
        }
    }
    
    // Для блога добавляем информацию о популярности тем
    if ($post_type === 'post') {
        $categories = wp_get_post_terms($post_id, 'category', array('fields' => 'ids'));
        if (!empty($categories)) {
            // Находим самую популярную категорию
            $posts_counts = array();
            foreach ($categories as $cat_id) {
                $posts_counts[$cat_id] = wp_count_posts_in_category($cat_id);
            }
            
            if (!empty($posts_counts)) {
                $popular_cat_id = array_keys($posts_counts, max($posts_counts))[0];
                $popular_cat = get_term($popular_cat_id, 'category');
                if ($popular_cat) {
                    $context['Популярная тематика'] = $popular_cat->name . ' (' . max($posts_counts) . ' статей)';
                }
            }
        }
    }
    
    return $context;
}

/**
 * Вспомогательная функция для подсчета записей в категории
 */
function wp_count_posts_in_category($category_id) {
    $posts = get_posts(array(
        'category' => $category_id,
        'post_status' => 'publish',
        'numberposts' => -1,
        'fields' => 'ids'
    ));
    return count($posts);
}

/**
 * Пример 5: Специальная обработка WooCommerce атрибутов
 */
add_filter('ai_assistant_taxonomies_context', 'enhance_woocommerce_attributes', 10, 3);
function enhance_woocommerce_attributes($context, $post_id, $post_type) {
    if ($post_type === 'product' && class_exists('WooCommerce')) {
        $product = wc_get_product($post_id);
        
        if ($product) {
            $attributes = $product->get_attributes();
            
            foreach ($attributes as $attribute) {
                // Только для публичных атрибутов
                if ($attribute->get_visible()) {
                    $attribute_name = wc_attribute_label($attribute->get_name());
                    
                    // Получаем значения атрибута
                    if ($attribute->is_taxonomy()) {
                        $values = wc_get_product_terms($post_id, $attribute->get_name(), array('fields' => 'names'));
                    } else {
                        $values = explode(' | ', $attribute->get_options()[0]);
                    }
                    
                    if (!empty($values)) {
                        // Добавляем специальные префиксы для важных атрибутов
                        switch ($attribute->get_name()) {
                            case 'pa_color':
                                $context['Цветовая гамма'] = implode(', ', $values);
                                break;
                            case 'pa_size':
                                $context['Доступные размеры'] = implode(', ', $values);
                                break;
                            case 'pa_material':
                                $context['Материал изготовления'] = implode(', ', $values);
                                break;
                            default:
                                $context[$attribute_name] = implode(', ', $values);
                        }
                    }
                }
            }
        }
    }
    
    return $context;
}

/**
 * Пример 10: Добавление кастомных мета-боксов в редактор
 */
add_action('add_meta_boxes', 'ai_assistant_add_meta_boxes');
function ai_assistant_add_meta_boxes() {
    
    add_meta_box(
        'ai-assistant-info',
        'AI Assistant Info',
        'ai_assistant_meta_box_callback',
        ['post', 'page', 'vacancy', 'employer'],
        'side',
        'default'
    );
}

function ai_assistant_meta_box_callback($post) {
    
    $last_generation = get_post_meta($post->ID, '_ai_assistant_generated_at', true);
    $generated_by = get_post_meta($post->ID, '_ai_assistant_generated_by', true);
    
    echo '<p><strong>Последняя генерация:</strong></p>';
    
    if ($last_generation) {
        echo '<p>' . date('d.m.Y H:i', strtotime($last_generation)) . '</p>';
        echo '<p><em>Модель: ' . ($generated_by ?: 'Неизвестно') . '</em></p>';
        
        echo '<p><button type="button" class="button regenerate-seo-btn" data-post-id="' . $post->ID . '">';
        echo 'Регенерировать SEO';
        echo '</button></p>';
    } else {
        echo '<p>SEO-данные еще не генерировались</p>';
        
        echo '<p><button type="button" class="button button-primary generate-seo-btn" data-post-id="' . $post->ID . '">';
        echo 'Генерировать SEO';
        echo '</button></p>';
    }
}

/**
 * ВАЖНО: Для использования этих примеров в реальном проекте:
 * 
 * 1. Создайте отдельный плагин или добавьте код в functions.php темы
 * 2. Адаптируйте примеры под ваши конкретные нужды
 * 3. Тестируйте каждое изменение в безопасной среде
 * 4. Следите за производительностью и логами
 * 5. Используйте только необходимые фильтры и действия
 */
