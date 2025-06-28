<?php
/**
 * Plugin Name: AI Assistant
 * Plugin URI: https://your-website.com/ai-assistant
 * Description: WordPress плагин для автоматической генерации SEO-данных с помощью OpenAI API, интегрированный с Yoast SEO.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://your-website.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-assistant
 * Domain Path: /languages
 */

// Запретить прямой доступ к файлу
if (!defined('ABSPATH')) {
    exit;
}

// Определяем константы плагина
define('AI_ASSISTANT_VERSION', '1.0.0');
define('AI_ASSISTANT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_ASSISTANT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AI_ASSISTANT_PLUGIN_FILE', __FILE__);

// Подключаем дополнительные файлы
require_once AI_ASSISTANT_PLUGIN_PATH . 'includes/hooks.php';

// Подключаем тестовый файл только в режиме разработки
if (defined('WP_DEBUG') && WP_DEBUG) {
    require_once AI_ASSISTANT_PLUGIN_PATH . 'test-openai-integration.php';
}

/**
 * Основной класс плагина AI Assistant
 */
class AI_Assistant {
    
    /**
     * Единственный экземпляр класса
     */
    private static $instance = null;
    
    /**
     * Получить единственный экземпляр класса
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Конструктор класса
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Инициализация колонок для списков записей
        add_action('admin_init', array($this, 'init_post_columns'));
        
        // Хук активации плагина
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Хук деактивации плагина
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // AJAX обработчики
        add_action('wp_ajax_ai_assistant_test_api', array($this, 'test_api_connection'));
        add_action('wp_ajax_ai_assistant_generate_seo', array($this, 'generate_seo_data'));
        add_action('wp_ajax_ai_assistant_save_seo_data', array($this, 'ajax_save_seo_data'));
        
        // Добавляем хук для действия 'generate_seo_data' (альтернативное имя)
        add_action('wp_ajax_generate_seo_data', array($this, 'generate_seo_data'));
    }
    
    /**
     * Получить поддерживаемые типы записей
     */
    public function get_supported_post_types() {
        $default_types = array('post', 'page', 'vacancy', 'employer');
        
        // Позволяем другим плагинам добавлять свои типы записей
        $supported_types = apply_filters('ai_assistant_supported_post_types', $default_types);
        
        // Проверяем, что типы записей действительно существуют
        $valid_types = array();
        foreach ($supported_types as $post_type) {
            if (post_type_exists($post_type)) {
                $valid_types[] = $post_type;
            }
        }
        
        return $valid_types;
    }
    
    /**
     * Инициализация колонок для списков записей
     */
    public function init_post_columns() {
        $supported_types = $this->get_supported_post_types();
        
        foreach ($supported_types as $post_type) {
            // Добавляем колонку для каждого типа записи
            add_filter("manage_{$post_type}_posts_columns", array($this, 'add_ai_assistant_column'));
            
            // Заполняем колонку данными
            add_action("manage_{$post_type}_posts_custom_column", array($this, 'fill_ai_assistant_column'), 10, 2);
        }
    }
    
    /**
     * Добавление колонки AI Assistant в списки записей
     */
    public function add_ai_assistant_column($columns) {
        // Вставляем колонку перед колонкой "Дата"
        $new_columns = array();
        foreach ($columns as $key => $value) {
            if ($key === 'date') {
                $new_columns['ai_assistant'] = __('AI Assistant', 'ai-assistant');
            }
            $new_columns[$key] = $value;
        }
        
        // Если колонки "date" нет, добавляем в конец
        if (!isset($new_columns['ai_assistant'])) {
            $new_columns['ai_assistant'] = __('AI Assistant', 'ai-assistant');
        }
        
        return $new_columns;
    }
    
    /**
     * Заполнение колонки AI Assistant данными
     */
    public function fill_ai_assistant_column($column_name, $post_id) {
        if ($column_name !== 'ai_assistant') {
            return;
        }
        
        // Проверяем мета-поля Yoast SEO
        $focus_keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        $seo_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        $meta_description = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        
        // Проверяем, заполнены ли все поля
        $all_fields_filled = !empty($focus_keyword) && !empty($seo_title) && !empty($meta_description);
        
        if ($all_fields_filled) {
            // Все поля заполнены - показываем статус "Оптимизировано"
            echo '<span class="ai-assistant-optimized">';
            echo '<span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-right: 5px;"></span>';
            echo '<span style="color: #46b450; font-weight: 500;">' . __('Оптимизировано', 'ai-assistant') . '</span>';
            echo '</span>';
        } else {
            // Есть пустые поля - показываем кнопку генерации
            $missing_fields = array();
            if (empty($focus_keyword)) $missing_fields[] = __('ключевое слово', 'ai-assistant');
            if (empty($seo_title)) $missing_fields[] = __('SEO заголовок', 'ai-assistant');
            if (empty($meta_description)) $missing_fields[] = __('мета-описание', 'ai-assistant');
            
            $missing_text = implode(', ', $missing_fields);
            
            echo '<button type="button" class="button button-small generate-seo-btn" ';
            echo 'data-post-id="' . esc_attr($post_id) . '" ';
            echo 'title="' . esc_attr(sprintf(__('Отсутствует: %s', 'ai-assistant'), $missing_text)) . '">';
            echo '<span class="dashicons dashicons-admin-generic" style="margin-right: 3px; font-size: 14px; line-height: 1;"></span>';
            echo __('Сгенерировать SEO', 'ai-assistant');
            echo '</button>';
            
            // Добавляем индикатор загрузки (скрытый по умолчанию)
            echo '<div class="ai-assistant-loading" style="display: none; margin-top: 5px;">';
            echo '<span class="spinner is-active" style="float: none; margin: 0;"></span>';
            echo '<span style="margin-left: 5px; font-size: 12px;">' . __('Генерация...', 'ai-assistant') . '</span>';
            echo '</div>';
        }
    }
    
    /**
     * Инициализация плагина
     */
    public function init() {
        // Загрузка текстового домена для переводов
        load_plugin_textdomain('ai-assistant', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Подключение скриптов и стилей для админ-панели
     */
    public function enqueue_admin_scripts($hook) {
        // Определяем страницы, где нужно подключать скрипты
        $allowed_hooks = array(
            'settings_page_ai-assistant', // Страница настроек
            'edit.php', // Список записей
            'upload.php' // Медиабиблиотека (для будущего использования)
        );
        
        // Проверяем, находимся ли мы на странице списка записей поддерживаемого типа
        $is_supported_post_list = false;
        if ($hook === 'edit.php') {
            $current_post_type = isset($_GET['post_type']) ? $_GET['post_type'] : 'post';
            $supported_types = $this->get_supported_post_types();
            $is_supported_post_list = in_array($current_post_type, $supported_types);
        }
        
        // Подключаем скрипты только на нужных страницах
        if (!in_array($hook, $allowed_hooks) && !$is_supported_post_list) {
            return;
        }
        
        // Подключаем CSS
        wp_enqueue_style(
            'ai-assistant-admin-style',
            AI_ASSISTANT_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            AI_ASSISTANT_VERSION
        );
        
        // Подключаем JavaScript
        wp_enqueue_script(
            'ai-assistant-admin-script',
            AI_ASSISTANT_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery'),
            AI_ASSISTANT_VERSION,
            true
        );
        
        // Передаем данные в JavaScript
        wp_localize_script('ai-assistant-admin-script', 'aiAssistant', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_assistant_nonce'),
            'current_hook' => $hook,
            'current_post_type' => isset($_GET['post_type']) ? $_GET['post_type'] : 'post',
            'api_key_configured' => !empty(get_option('ai_assistant_api_key', '')),
            'strings' => array(
                'test_connection' => __('Тестирование соединения...', 'ai-assistant'),
                'connection_success' => __('Соединение успешно!', 'ai-assistant'),
                'connection_failed' => __('Ошибка соединения', 'ai-assistant'),
                'generating_seo' => __('Генерация SEO-данных...', 'ai-assistant'),
                'generation_success' => __('SEO-данные успешно сгенерированы!', 'ai-assistant'),
                'generation_failed' => __('Ошибка генерации SEO-данных', 'ai-assistant'),
                'confirm_generate' => __('Сгенерировать SEO-данные для этой записи?', 'ai-assistant'),
                'api_key_required' => __('Сначала настройте OpenAI API ключ в настройках плагина', 'ai-assistant'),
                'optimized' => __('Оптимизировано', 'ai-assistant'),
                'timeout_error' => __('Превышено время ожидания ответа от OpenAI API', 'ai-assistant'),
                'request_cancelled' => __('Запрос был отменен', 'ai-assistant'),
                'access_denied' => __('Доступ запрещен - проверьте права пользователя', 'ai-assistant'),
                'server_error' => __('Внутренняя ошибка сервера', 'ai-assistant'),
                'service_unavailable' => __('Сервис OpenAI временно недоступен', 'ai-assistant'),
                'invalid_post_id' => __('Некорректный ID записи', 'ai-assistant'),
                'network_error' => __('Ошибка сети - проверьте подключение к интернету', 'ai-assistant'),
            )
        ));
    }
    
    /**
     * Добавление страницы в меню админ-панели
     */
    public function add_admin_menu() {
        add_options_page(
            __('AI Assistant Settings', 'ai-assistant'),
            __('AI Assistant', 'ai-assistant'),
            'manage_options',
            'ai-assistant',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Инициализация настроек
     */
    public function settings_init() {
        // Регистрируем группу настроек
        register_setting('ai_assistant_settings', 'ai_assistant_api_key', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        // Добавляем секцию настроек
        add_settings_section(
            'ai_assistant_api_section',
            __('API Configuration', 'ai-assistant'),
            array($this, 'api_section_callback'),
            'ai_assistant_settings'
        );
        
        // Добавляем поле для API ключа
        add_settings_field(
            'ai_assistant_api_key',
            __('OpenAI API Key', 'ai-assistant'),
            array($this, 'api_key_field_callback'),
            'ai_assistant_settings',
            'ai_assistant_api_section'
        );
    }
    
    /**
     * Callback для секции API настроек
     */
    public function api_section_callback() {
        echo '<p>' . __('Введите ваш OpenAI API ключ для использования возможностей искусственного интеллекта.', 'ai-assistant') . '</p>';
    }
    
    /**
     * Callback для поля API ключа
     */
    public function api_key_field_callback() {
        $api_key = get_option('ai_assistant_api_key', '');
        $masked_key = $api_key ? substr($api_key, 0, 8) . str_repeat('*', strlen($api_key) - 8) : '';
        
        echo '<input type="password" id="ai_assistant_api_key" name="ai_assistant_api_key" value="' . esc_attr($api_key) . '" class="regular-text" placeholder="sk-..." />';
        echo '<button type="button" id="toggle-api-key" class="button button-secondary" style="margin-left: 10px;">' . __('Показать/Скрыть', 'ai-assistant') . '</button>';
        echo '<button type="button" id="test-api-key" class="button button-secondary" style="margin-left: 10px;">' . __('Тест соединения', 'ai-assistant') . '</button>';
        echo '<p class="description">' . __('Получите API ключ на сайте OpenAI: https://platform.openai.com/api-keys', 'ai-assistant') . '</p>';
        echo '<div id="api-test-result" style="margin-top: 10px;"></div>';
    }
    
    /**
     * Страница настроек плагина
     */
    public function settings_page() {
        // Проверяем права доступа
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Сохранение настроек
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'ai_assistant_messages',
                'ai_assistant_message',
                __('Настройки сохранены', 'ai-assistant'),
                'updated'
            );
        }
        
        settings_errors('ai_assistant_messages');
        ?>
        <div class="wrap ai-assistant-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ai-assistant-header">
                <h2><?php _e('Настройки AI Assistant', 'ai-assistant'); ?></h2>
                <p><?php _e('Настройте плагин для автоматической генерации SEO-данных с помощью искусственного интеллекта.', 'ai-assistant'); ?></p>
            </div>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('ai_assistant_settings');
                do_settings_sections('ai_assistant_settings');
                submit_button(__('Сохранить настройки', 'ai-assistant'));
                ?>
            </form>
            
            <div class="ai-assistant-info">
                <h3><?php _e('Информация о плагине', 'ai-assistant'); ?></h3>
                <ul>
                    <li><strong><?php _e('Версия:', 'ai-assistant'); ?></strong> <?php echo AI_ASSISTANT_VERSION; ?></li>
                    <li><strong><?php _e('Статус Yoast SEO:', 'ai-assistant'); ?></strong> 
                        <?php 
                        if (class_exists('WPSEO_Options')) {
                            echo '<span style="color: green;">' . __('Активен', 'ai-assistant') . '</span>';
                        } else {
                            echo '<span style="color: red;">' . __('Не установлен', 'ai-assistant') . '</span>';
                        }
                        ?>
                    </li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX обработчик для генерации SEO-данных
     */
    public function generate_seo_data() {
        // Проверяем nonce для безопасности (стандартный WordPress способ)
        check_ajax_referer('ai_assistant_nonce', 'nonce');
        
        // Проверяем права доступа
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Недостаточно прав доступа для редактирования записей', 'ai-assistant'));
        }
        
        // Валидация post_id
        $post_id = intval($_POST['post_id']);
        if (empty($post_id) || $post_id <= 0) {
            wp_send_json_error(__('Не указан корректный ID записи', 'ai-assistant'));
        }
        
        // Проверяем, что запись существует
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Запись с указанным ID не найдена', 'ai-assistant'));
        }
        
        // Проверяем, что пользователь может редактировать эту конкретную запись
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('У вас нет прав для редактирования этой записи', 'ai-assistant'));
        }
        
        // Получаем тип поста
        $post_type = get_post_type($post_id);
        
        // Проверяем, что тип записи поддерживается
        $supported_types = $this->get_supported_post_types();
        if (!in_array($post_type, $supported_types)) {
            wp_send_json_error(__('Данный тип записи не поддерживается плагином', 'ai-assistant'));
        }
        
        // Получаем API ключ из настроек
        $api_key = get_option('ai_assistant_api_key', '');
        if (empty($api_key)) {
            wp_send_json_error(__('API ключ OpenAI не настроен в настройках плагина', 'ai-assistant'));
        }
        
        // Подготавливаем контекст для AI
        $context = $this->prepare_ai_context($post, $post_type);
        
        // Дополнительная проверка минимального содержимого
        if (empty($context['title']) && empty($context['content'])) {
            wp_send_json_error(__('Запись должна содержать заголовок или текст для генерации SEO-данных', 'ai-assistant'));
        }
        
        // Проверяем, не заполнены ли уже все SEO-поля
        $existing_seo = $this->get_existing_seo_data($post_id);
        if ($existing_seo['all_filled']) {
            wp_send_json_error(__('Все SEO-поля уже заполнены для этой записи', 'ai-assistant'));
        }
        
        // Логируем начало генерации
        error_log('AI Assistant: Starting SEO generation for post ' . $post_id . ' (' . $post_type . ')');
        
        // Генерируем SEO-данные с улучшенным контекстом
        $seo_data = $this->generate_seo_with_context($context, $api_key, $post_type);
        
        if ($seo_data['success']) {
            // НЕ сохраняем данные автоматически, только возвращаем для модального окна
            // Логируем успешную генерацию
            error_log('AI Assistant: SEO data generated successfully for post ' . $post_id . ' (ready for manual save)');
            
            wp_send_json_success(array(
                'message' => __('SEO-данные успешно сгенерированы', 'ai-assistant'),
                'data' => $seo_data['data'],
                'post_id' => $post_id,
                'post_title' => $post->post_title,
                'post_type' => $post_type,
                'context_used' => $context, // Для отладки
                'auto_saved' => false // Указываем, что данные НЕ сохранены автоматически
            ));
        } else {
            // Логируем ошибку генерации
            error_log('AI Assistant: SEO generation failed for post ' . $post_id . ': ' . $seo_data['message']);
            
            wp_send_json_error($seo_data['message']);
        }
    }
    
    /**
     * Подготовка контекста для AI с учетом типа записи
     */
    private function prepare_ai_context($post, $post_type) {
        $context = array(
            'title' => $post->post_title,
            'content' => wp_strip_all_tags($post->post_content),
            'excerpt' => $post->post_excerpt,
            'post_type' => $post_type,
            'custom_fields' => array()
        );
        
        // Ограничиваем длину основного контента
        $context['content'] = wp_trim_words($context['content'], 300);
        
        // Если нет excerpt, создаем из контента
        if (empty($context['excerpt']) && !empty($context['content'])) {
            $context['excerpt'] = wp_trim_words($context['content'], 30);
        }
        
        // Получаем специфичные для типа записи данные
        switch ($post_type) {
            case 'vacancy':
                $context['custom_fields'] = $this->get_vacancy_context($post->ID);
                break;
                
            case 'employer':
                $context['custom_fields'] = $this->get_employer_context($post->ID);
                break;
                
            case 'product':
                $context['custom_fields'] = $this->get_product_context($post->ID);
                break;
                
            default:
                // Для стандартных типов записей получаем категории и теги
                $context['custom_fields'] = $this->get_standard_post_context($post->ID, $post_type);
                break;
        }
        
        return $context;
    }
    
    /**
     * Получение контекста для вакансий
     */
    private function get_vacancy_context($post_id) {
        $context = array();
        
        // Основные поля вакансии
        $fields = array(
            'company_name' => 'Название компании',
            'job_location' => 'Местоположение',
            'job_type' => 'Тип занятости',
            'salary_min' => 'Минимальная зарплата',
            'salary_max' => 'Максимальная зарплата',
            'experience_level' => 'Уровень опыта',
            'job_category' => 'Категория',
            'skills_required' => 'Требуемые навыки',
            'job_benefits' => 'Льготы и преимущества'
        );
        
        foreach ($fields as $field => $description) {
            $value = get_post_meta($post_id, $field, true);
            if (!empty($value)) {
                $context[$description] = $value;
            }
        }
        
        // Получаем таксономии через универсальную функцию
        $taxonomy_context = $this->get_taxonomies_context($post_id, 'vacancy');
        $context = array_merge($context, $taxonomy_context);
        
        return $context;
    }
    
    /**
     * Получение контекста для работодателей
     */
    private function get_employer_context($post_id) {
        $context = array();
        
        // Основные поля работодателя
        $fields = array(
            'company_name' => 'Название компании',
            'company_description' => 'Описание компании',
            'company_industry' => 'Отрасль',
            'company_size' => 'Размер компании',
            'company_location' => 'Местоположение',
            'company_website' => 'Веб-сайт',
            'company_founded' => 'Год основания',
            'company_specialization' => 'Специализация'
        );
        
        foreach ($fields as $field => $description) {
            $value = get_post_meta($post_id, $field, true);
            if (!empty($value)) {
                $context[$description] = $value;
            }
        }
        
        // Получаем таксономии через универсальную функцию
        $taxonomy_context = $this->get_taxonomies_context($post_id, 'employer');
        $context = array_merge($context, $taxonomy_context);
        
        return $context;
    }
    
    /**
     * Получение контекста для товаров (WooCommerce) с универсальной поддержкой таксономий
     */
    private function get_product_context($post_id) {
        $context = array();
        
        // Проверяем, активен ли WooCommerce
        if (class_exists('WooCommerce')) {
            $product = wc_get_product($post_id);
            if ($product) {
                $context['Цена'] = $product->get_price_html();
                $context['Краткое описание'] = $product->get_short_description();
                $context['SKU'] = $product->get_sku();
                $context['Статус товара'] = $product->get_status();
                $context['Тип товара'] = $product->get_type();
                
                // Дополнительные атрибуты WooCommerce
                $attributes = $product->get_attributes();
                foreach ($attributes as $attribute) {
                    if ($attribute->get_visible()) {
                        $values = wc_get_product_terms($post_id, $attribute->get_name(), array('fields' => 'names'));
                        if (!empty($values)) {
                            $context[wc_attribute_label($attribute->get_name())] = implode(', ', $values);
                        }
                    }
                }
            }
        }
        
        // Получаем ВСЕ таксономии товара через универсальную функцию
        $taxonomy_context = $this->get_taxonomies_context($post_id, 'product');
        $context = array_merge($context, $taxonomy_context);
        
        return $context;
    }
    
    /**
     * Получение контекста для стандартных типов записей
     */
    private function get_standard_post_context($post_id, $post_type) {
        // Получаем ВСЕ таксономии для данного типа записи
        $context = $this->get_taxonomies_context($post_id, $post_type);
        
        // Дополнительные мета-поля (если есть)
        $common_meta_fields = array(
            'location' => 'Местоположение',
            'event_date' => 'Дата события',
            'price' => 'Цена',
            'author_name' => 'Автор'
        );
        
        foreach ($common_meta_fields as $field => $description) {
            $value = get_post_meta($post_id, $field, true);
            if (!empty($value)) {
                $context[$description] = $value;
            }
        }
        
        return $context;
    }
    
    /**
     * Получение существующих SEO-данных
     */
    private function get_existing_seo_data($post_id) {
        $focus_keyword = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        $seo_title = get_post_meta($post_id, '_yoast_wpseo_title', true);
        $meta_description = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        
        return array(
            'focus_keyword' => $focus_keyword,
            'seo_title' => $seo_title,
            'meta_description' => $meta_description,
            'all_filled' => !empty($focus_keyword) && !empty($seo_title) && !empty($meta_description)
        );
    }
    
    /**
     * Универсальное получение контекста таксономий для любого типа записи
     */
    private function get_taxonomies_context($post_id, $post_type) {
        $context = array();
        
        // Получаем все таксономии для данного типа записи
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        
        if (empty($taxonomies)) {
            return $context;
        }
        
        // Фильтр для исключения определенных таксономий
        $excluded_taxonomies = apply_filters('ai_assistant_excluded_taxonomies', array(
            'nav_menu', 
            'link_category', 
            'post_format'
        ), $post_type, $post_id);
        
        foreach ($taxonomies as $taxonomy_slug => $taxonomy_object) {
            // Пропускаем исключенные таксономии
            if (in_array($taxonomy_slug, $excluded_taxonomies)) {
                continue;
            }
            
            // Получаем термины для данной таксономии
            $terms = wp_get_post_terms($post_id, $taxonomy_slug, array('fields' => 'names'));
            
            if (!empty($terms) && !is_wp_error($terms)) {
                // Определяем понятное название таксономии
                $taxonomy_name = $this->get_taxonomy_display_name($taxonomy_slug, $taxonomy_object);
                
                // Фильтр для возможности модификации названия таксономии
                $taxonomy_name = apply_filters('ai_assistant_taxonomy_display_name', $taxonomy_name, $taxonomy_slug, $taxonomy_object, $post_type);
                
                $context[$taxonomy_name] = implode(', ', $terms);
                
                // Логируем для отладки
                $this->log_debug("Found taxonomy '{$taxonomy_slug}' with terms: " . implode(', ', $terms), array(
                    'post_id' => $post_id,
                    'post_type' => $post_type,
                    'taxonomy' => $taxonomy_slug,
                    'terms_count' => count($terms)
                ));
            }
        }
        
        // Фильтр для возможности добавления дополнительного контекста таксономий
        $context = apply_filters('ai_assistant_taxonomies_context', $context, $post_id, $post_type);
        
        return $context;
    }
    
    /**
     * Получение понятного названия таксономии с поддержкой популярных плагинов
     */
    private function get_taxonomy_display_name($taxonomy_slug, $taxonomy_object) {
        // Предопределенные переводы популярных таксономий
        $translations = array(
            // WordPress стандартные
            'category' => 'Категории',
            'post_tag' => 'Теги',
            
            // WooCommerce
            'product_cat' => 'Категории товаров',
            'product_tag' => 'Теги товаров',
            'pa_color' => 'Цвет',
            'pa_size' => 'Размер',
            'product_type' => 'Тип товара',
            'product_visibility' => 'Видимость товара',
            'product_shipping_class' => 'Класс доставки',
            
            // Job Manager / WP Job Manager
            'job_listing_category' => 'Категории вакансий',
            'job_listing_type' => 'Тип работы',
            'job_listing_location' => 'Местоположение работы',
            'job_listing_tag' => 'Теги вакансий',
            'resume_category' => 'Категории резюме',
            'resume_skill' => 'Навыки',
            
            // Кастомные для проекта
            'job_category' => 'Категории вакансий',
            'job_type' => 'Тип работы',
            'job_location' => 'Местоположение работы',
            'employer_category' => 'Категории работодателей',
            'company_size' => 'Размер компании',
            'industry' => 'Отрасль',
            'skill' => 'Навыки',
            'experience_level' => 'Уровень опыта',
            
            // События
            'event_category' => 'Категории событий',
            'event_tag' => 'Теги событий',
            'event_location' => 'Место проведения',
            'event_type' => 'Тип события',
            
            // Портфолио
            'portfolio_category' => 'Категории портфолио',
            'portfolio_tag' => 'Теги портфолио',
            'portfolio_skill' => 'Навыки',
            
            // Услуги
            'service_category' => 'Категории услуг',
            'service_tag' => 'Теги услуг',
            'service_location' => 'Регион услуг',
            
            // Недвижимость
            'property_type' => 'Тип недвижимости',
            'property_status' => 'Статус недвижимости',
            'property_location' => 'Местоположение',
            'property_feature' => 'Особенности',
            
            // Образование
            'course_category' => 'Категории курсов',
            'course_tag' => 'Теги курсов',
            'course_level' => 'Уровень сложности',
            'course_duration' => 'Продолжительность',
            
            // FAQ/База знаний
            'faq_category' => 'Категории FAQ',
            'kb_category' => 'Категории базы знаний',
            'kb_tag' => 'Теги базы знаний'
        );
        
        // Фильтр для возможности добавления новых переводов
        $translations = apply_filters('ai_assistant_taxonomy_translations', $translations, $taxonomy_slug, $taxonomy_object);
        
        // Если есть предопределенный перевод
        if (isset($translations[$taxonomy_slug])) {
            return $translations[$taxonomy_slug];
        }
        
        // Используем название из объекта таксономии
        if (isset($taxonomy_object->label) && !empty($taxonomy_object->label)) {
            return $taxonomy_object->label;
        }
        
        // В крайнем случае используем slug с заглавной буквы
        return ucfirst(str_replace(array('_', '-'), ' ', $taxonomy_slug));
    }

    // ...existing code...
    
    /**
     * Генерация SEO-данных с расширенным контекстом
     */
    private function generate_seo_with_context($context, $api_key, $post_type) {
        // Создаем улучшенный промпт
        $prompt = $this->create_advanced_seo_prompt($context, $post_type);
        
        // Отправляем запрос к OpenAI API с новой моделью
        $response = $this->call_openai_api_advanced($api_key, $prompt);
        
        if ($response['success']) {
            return $this->parse_seo_response($response['data']);
        } else {
            return $response;
        }
    }
    
    /**
     * Создание продвинутого промпта для генерации SEO-данных
     */
    private function create_advanced_seo_prompt($context, $post_type) {
        $prompt = "Ты - эксперт по SEO-оптимизации с глубокими знаниями современных алгоритмов поисковых систем. ";
        $prompt .= "Проанализируй следующий контент и верни ответ строго в формате JSON, без лишнего текста. ";
        $prompt .= "JSON должен содержать три ключа: focus_keyword, seo_title и meta_description.\n\n";
        
        // Добавляем контекст в зависимости от типа записи
        $prompt .= "ТИП КОНТЕНТА: " . $this->get_post_type_description($post_type) . "\n\n";
        
        // Основная информация
        $prompt .= "ЗАГОЛОВОК: " . $context['title'] . "\n\n";
        
        if (!empty($context['excerpt'])) {
            $prompt .= "КРАТКОЕ ОПИСАНИЕ: " . $context['excerpt'] . "\n\n";
        }
        
        if (!empty($context['content'])) {
            $prompt .= "ОСНОВНОЙ КОНТЕНТ: " . $context['content'] . "\n\n";
        }
        
        // Дополнительные поля
        if (!empty($context['custom_fields'])) {
            $prompt .= "ДОПОЛНИТЕЛЬНАЯ ИНФОРМАЦИЯ:\n";
            foreach ($context['custom_fields'] as $field => $value) {
                $prompt .= "- {$field}: {$value}\n";
            }
            $prompt .= "\n";
        }
        
        // Инструкции для AI с жесткими ограничениями по длине
        $prompt .= "СТРОГИЕ ТРЕБОВАНИЯ К ГЕНЕРАЦИИ:\n";
        $prompt .= "1. focus_keyword: Основное ключевое слово или фраза (МАКСИМУМ 3-4 слова)\n";
        $prompt .= "2. seo_title: SEO-заголовок (СТРОГО 45-55 символов!), содержащий ключевое слово в начале\n";
        $prompt .= "3. meta_description: Мета-описание (СТРОГО 140-155 символов!), с призывом к действию в конце\n\n";
        
        $prompt .= "🚨 КРИТИЧЕСКИ ВАЖНО:\n";
        $prompt .= "- SEO заголовок НЕ ДОЛЖЕН превышать 55 символов\n";
        $prompt .= "- Мета-описание НЕ ДОЛЖНО превышать 155 символов\n";
        $prompt .= "- Размещай ключевые слова в начале заголовка\n";
        $prompt .= "- Заканчивай описание призывом к действию\n";
        $prompt .= "- Избегай лишних слов и символов\n\n";
        
        // Специфичные инструкции по типу контента
        $prompt .= $this->get_post_type_seo_instructions($post_type);
        
        $prompt .= "\nФОРМАТ ОТВЕТА:\n";
        $prompt .= "{\n";
        $prompt .= '  "focus_keyword": "ключевое слово",'."\n";
        $prompt .= '  "seo_title": "привлекательный SEO-заголовок",'."\n";
        $prompt .= '  "meta_description": "мотивирующее мета-описание"'."\n";
        $prompt .= "}\n\n";
        
        $prompt .= "ВАЖНО: Отвечай ТОЛЬКО JSON без дополнительного текста или пояснений.";
        
        return $prompt;
    }
    
    /**
     * Получение описания типа записи для промпта
     */
    private function get_post_type_description($post_type) {
        $descriptions = array(
            'post' => 'Статья блога',
            'page' => 'Информационная страница сайта',
            'vacancy' => 'Вакансия для поиска работы',
            'employer' => 'Страница работодателя/компании',
            'product' => 'Товар интернет-магазина',
            'event' => 'Мероприятие или событие'
        );
        
        return isset($descriptions[$post_type]) ? $descriptions[$post_type] : 'Контент сайта';
    }
    
    /**
     * Получение специфичных SEO-инструкций по типу записи
     */
    private function get_post_type_seo_instructions($post_type) {
        switch ($post_type) {
            case 'vacancy':
                return "СПЕЦИАЛЬНЫЕ ТРЕБОВАНИЯ ДЛЯ ВАКАНСИЙ:\n" .
                       "- Включи название должности в ключевое слово\n" .
                       "- В заголовке укажи уровень или местоположение\n" .
                       "- В описании упомяни компанию и основные требования\n";
                       
            case 'employer':
                return "СПЕЦИАЛЬНЫЕ ТРЕБОВАНИЯ ДЛЯ РАБОТОДАТЕЛЕЙ:\n" .
                       "- Ключевое слово должно включать название компании или отрасль\n" .
                       "- Заголовок должен отражать деятельность компании\n" .
                       "- Описание должно привлекать потенциальных сотрудников\n";
                       
            case 'product':
                return "СПЕЦИАЛЬНЫЕ ТРЕБОВАНИЯ ДЛЯ ТОВАРОВ:\n" .
                       "- Ключевое слово должно быть коммерческим запросом\n" .
                       "- Заголовок может включать цену или особенности\n" .
                       "- Описание должно мотивировать к покупке\n";
                       
            default:
                return "ОБЩИЕ SEO-ПРИНЦИПЫ:\n" .
                       "- Используй релевантные поисковые запросы\n" .
                       "- Создавай привлекательные заголовки\n" .
                       "- Пиши описания, мотивирующие к клику\n";
        }
    }
    
    /**
     * Продвинутый вызов OpenAI API с новой моделью
     */
    private function call_openai_api_advanced($api_key, $prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => 'gpt-4.1-2025-04-14', // Используем самую новую модель GPT-4.1
            'messages' => array(
                array(
                    'role' => 'system',
                    'content' => 'Ты эксперт по SEO-оптимизации. Всегда отвечай строго в формате JSON без дополнительного текста.'
                ),
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 600,
            'temperature' => 0.3, // Низкая температура для более предсказуемых результатов
            'top_p' => 0.9,
            'frequency_penalty' => 0.0,
            'presence_penalty' => 0.0
        );
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'User-Agent' => 'WordPress AI Assistant Plugin/' . AI_ASSISTANT_VERSION
            ),
            'body' => json_encode($data),
            'timeout' => 45, // Увеличиваем timeout для GPT-4
            'method' => 'POST',
            'sslverify' => true
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
        
        // Детальная обработка ошибок API
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            
            if (isset($error_data['error'])) {
                $error = $error_data['error'];
                $error_message = isset($error['message']) ? $error['message'] : 'Неизвестная ошибка API';
                
                // Специфичные сообщения для разных типов ошибок
                switch ($error['type'] ?? '') {
                    case 'insufficient_quota':
                        $error_message = 'Превышена квота API OpenAI. Проверьте баланс аккаунта.';
                        break;
                    case 'invalid_api_key':
                        $error_message = 'Недействительный API ключ OpenAI.';
                        break;
                    case 'model_not_found':
                        $error_message = 'Указанная модель GPT не найдена или недоступна.';
                        break;
                    case 'rate_limit_exceeded':
                        $error_message = 'Превышен лимит запросов к API OpenAI.';
                        break;
                }
                
                error_log('AI Assistant API Error: ' . $error_message . ' (Code: ' . $response_code . ')');
            } else {
                $error_message = 'HTTP ошибка ' . $response_code;
            }
            
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
        
        $data = json_decode($response_body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'data' => $data['choices'][0]['message']['content'],
                'usage' => $data['usage'] ?? null // Информация об использовании токенов
            );
        }
        
        return array(
            'success' => false,
            'message' => 'Неверный формат ответа от OpenAI API'
        );
    }
    
    /**
     * Генерация SEO-данных для записи с помощью OpenAI (старый метод - оставляем для совместимости)
     */
    private function generate_seo_for_post($post, $api_key) {
        // Подготавливаем контент записи
        $content = wp_strip_all_tags($post->post_content);
        $title = $post->post_title;
        $excerpt = $post->post_excerpt ? $post->post_excerpt : wp_trim_words($content, 30);
        
        // Ограничиваем длину контента для API
        $content = wp_trim_words($content, 200);
        
        // Создаем промпт для OpenAI
        $prompt = $this->create_seo_prompt($title, $content, $excerpt);
        
        // Отправляем запрос к OpenAI API
        $response = $this->call_openai_api($api_key, $prompt);
        
        if ($response['success']) {
            return $this->parse_seo_response($response['data']);
        } else {
            return $response;
        }
    }
    
    /**
     * Создание промпта для генерации SEO-данных
     */
    private function create_seo_prompt($title, $content, $excerpt) {
        $prompt = "Ты - эксперт по SEO-оптимизации. На основе следующей информации о статье создай SEO-данные:\n\n";
        $prompt .= "Заголовок: {$title}\n";
        $prompt .= "Краткое описание: {$excerpt}\n";
        $prompt .= "Содержание: {$content}\n\n";
        $prompt .= "Создай следующие SEO-данные в формате JSON:\n";
        $prompt .= "{\n";
        $prompt .= '  "focus_keyword": "основное ключевое слово или фраза",'."\n";
        $prompt .= '  "seo_title": "SEO-заголовок (до 60 символов)",'."\n";
        $prompt .= '  "meta_description": "мета-описание (до 160 символов)"'."\n";
        $prompt .= "}\n\n";
        $prompt .= "Требования:\n";
        $prompt .= "- Ключевое слово должно быть релевантным и часто используемым\n";
        $prompt .= "- SEO-заголовок должен содержать ключевое слово и быть привлекательным\n";
        $prompt .= "- Мета-описание должно мотивировать к клику и содержать ключевое слово\n";
        $prompt .= "- Отвечай только JSON без дополнительного текста";
        
        return $prompt;
    }
    
    /**
     * Вызов OpenAI API
     */
    private function call_openai_api($api_key, $prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $data = array(
            'model' => 'gpt-3.5-turbo',
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt
                )
            ),
            'max_tokens' => 500,
            'temperature' => 0.7
        );
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($data),
            'timeout' => 30,
            'method' => 'POST'
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) ? 
                $error_data['error']['message'] : 
                __('Ошибка API OpenAI', 'ai-assistant');
                
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
        
        $data = json_decode($response_body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            return array(
                'success' => true,
                'data' => $data['choices'][0]['message']['content']
            );
        }
        
        return array(
            'success' => false,
            'message' => __('Неверный ответ от OpenAI API', 'ai-assistant')
        );
    }
    
    /**
     * Парсинг ответа от OpenAI и извлечение SEO-данных с оптимизацией длины
     */
    private function parse_seo_response($response_content) {
        // Пытаемся извлечь JSON из ответа
        $json_start = strpos($response_content, '{');
        $json_end = strrpos($response_content, '}');
        
        if ($json_start !== false && $json_end !== false) {
            $json_string = substr($response_content, $json_start, $json_end - $json_start + 1);
            $seo_data = json_decode($json_string, true);
            
            if (json_last_error() === JSON_ERROR_NONE && 
                isset($seo_data['focus_keyword']) && 
                isset($seo_data['seo_title']) && 
                isset($seo_data['meta_description'])) {
                
                // Оптимизируем длину SEO-данных
                $optimized_data = $this->optimize_seo_data_length($seo_data);
                
                return array(
                    'success' => true,
                    'data' => $optimized_data
                );
            }
        }
        
        return array(
            'success' => false,
            'message' => __('Не удалось распарсить ответ от OpenAI', 'ai-assistant')
        );
    }
    
    /**
     * Оптимизация длины SEO-данных согласно рекомендациям Google
     */
    private function optimize_seo_data_length($seo_data) {
        // Оптимизируем SEO заголовок (максимум 55 символов)
        $seo_title = sanitize_text_field($seo_data['seo_title']);
        if (mb_strlen($seo_title) > 55) {
            // Обрезаем до 52 символов и добавляем многоточие
            $seo_title = mb_substr($seo_title, 0, 52) . '...';
            
            // Логируем обрезание
            error_log('AI Assistant: SEO title was truncated from ' . mb_strlen($seo_data['seo_title']) . ' to 55 characters');
        }
        
        // Оптимизируем мета-описание (максимум 155 символов)
        $meta_description = sanitize_textarea_field($seo_data['meta_description']);
        if (mb_strlen($meta_description) > 155) {
            // Обрезаем до 152 символов и добавляем многоточие
            $meta_description = mb_substr($meta_description, 0, 152) . '...';
            
            // Логируем обрезание
            error_log('AI Assistant: Meta description was truncated from ' . mb_strlen($seo_data['meta_description']) . ' to 155 characters');
        }
        
        // Оптимизируем ключевое слово (максимум 50 символов)
        $focus_keyword = sanitize_text_field($seo_data['focus_keyword']);
        if (mb_strlen($focus_keyword) > 50) {
            $focus_keyword = mb_substr($focus_keyword, 0, 47) . '...';
            
            // Логируем обрезание
            error_log('AI Assistant: Focus keyword was truncated from ' . mb_strlen($seo_data['focus_keyword']) . ' to 50 characters');
        }
        
        return array(
            'focus_keyword' => $focus_keyword,
            'seo_title' => $seo_title,
            'meta_description' => $meta_description
        );
    }
    
    /**
     * DEPRECATED: Функция сохранения SEO-данных (больше не используется)
     * Данные теперь сохраняются только через ajax_save_seo_data после подтверждения пользователя
     */
    private function save_seo_data_deprecated($post_id, $seo_data) {
        // DEPRECATED: Эта функция больше не используется
        // SEO-данные теперь сохраняются только через модальное окно
        return false;
    }
    
    /**
     * AJAX обработчик для тестирования API соединения
     */
    public function test_api_connection() {
        // Проверяем nonce для безопасности
        if (!wp_verify_nonce($_POST['nonce'], 'ai_assistant_nonce')) {
            wp_die(__('Ошибка безопасности', 'ai-assistant'));
        }
        
        // Проверяем права доступа
        if (!current_user_can('manage_options')) {
            wp_die(__('Недостаточно прав доступа', 'ai-assistant'));
        }
        
        $api_key = sanitize_text_field($_POST['api_key']);
        
        if (empty($api_key)) {
            wp_send_json_error(__('API ключ не может быть пустым', 'ai-assistant'));
        }
        
        // Тестируем соединение с OpenAI API
        $response = $this->test_openai_connection($api_key);
        
        if ($response['success']) {
            wp_send_json_success($response['data']);
        } else {
            wp_send_json_error($response['message']);
        }
    }
    
    /**
     * Тестирование соединения с OpenAI API
     */
    private function test_openai_connection($api_key) {
        $url = 'https://api.openai.com/v1/models';
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'timeout' => 30,
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) ? 
                $error_data['error']['message'] : 
                __('Неизвестная ошибка API', 'ai-assistant');
                
            return array(
                'success' => false,
                'message' => $error_message
            );
        }
        
        $data = json_decode($response_body, true);
        
        if (isset($data['data']) && is_array($data['data']) && count($data['data']) > 0) {
            // Находим подходящую модель для генерации текста
            $available_models = array_column($data['data'], 'id');
            $preferred_models = array('gpt-4', 'gpt-3.5-turbo', 'text-davinci-003');
            
            $selected_model = 'gpt-3.5-turbo'; // По умолчанию
            foreach ($preferred_models as $model) {
                if (in_array($model, $available_models)) {
                    $selected_model = $model;
                    break;
                }
            }
            
            return array(
                'success' => true,
                'data' => array(
                    'model' => $selected_model,
                    'available_models' => count($available_models),
                    'message' => __('Соединение успешно установлено', 'ai-assistant')
                )
            );
        }
        
        return array(
            'success' => false,
            'message' => __('Неверный ответ от API', 'ai-assistant')
        );
    }
    
    /**
     * Активация плагина
     */
    public function activate() {
        // Создаем необходимые опции при активации
        add_option('ai_assistant_api_key', '');
        
        // Проверяем версию WordPress
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('Этот плагин требует WordPress версии 5.0 или выше.', 'ai-assistant'));
        }
    }
    
    /**
     * Деактивация плагина
     */
    /**
     * AJAX обработчик для сохранения отредактированных SEO-данных
     */
    public function ajax_save_seo_data() {
        // Проверяем nonce для безопасности
        check_ajax_referer('ai_assistant_nonce', 'nonce');
        
        // Проверяем права доступа
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Недостаточно прав для выполнения этого действия', 'ai-assistant'));
        }
        
        // Получаем данные из запроса
        $post_id = intval($_POST['post_id']);
        $seo_data = $_POST['seo_data'];
        
        // Валидация данных
        if (!$post_id || $post_id <= 0) {
            wp_send_json_error(__('Некорректный ID записи', 'ai-assistant'));
        }
        
        if (!is_array($seo_data)) {
            wp_send_json_error(__('Некорректные данные SEO', 'ai-assistant'));
        }
        
        // Проверяем существование записи
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Запись не найдена', 'ai-assistant'));
        }
        
        // Проверяем права на редактирование конкретной записи
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(__('У вас нет прав для редактирования этой записи', 'ai-assistant'));
        }
        
        try {
            // Применяем фильтр для возможности модификации данных перед сохранением
            $seo_data = apply_filters('ai_assistant_before_save_seo_data', $seo_data, $post_id);
            
            // Очищаем и валидируем данные
            $seo_title = sanitize_text_field($seo_data['seo_title']);
            $meta_description = sanitize_textarea_field($seo_data['meta_description']);
            $focus_keyword = sanitize_text_field($seo_data['focus_keyword']);
            
            // Валидация обязательных полей
            if (empty($seo_title)) {
                wp_send_json_error(__('SEO заголовок не может быть пустым', 'ai-assistant'));
            }
            
            // Сохраняем данные в мета-поля Yoast SEO
            $result = array(
                'title_saved' => false,
                'description_saved' => false,
                'keyword_saved' => false
            );
            
            // Сохраняем SEO заголовок
            if (!empty($seo_title)) {
                $result['title_saved'] = update_post_meta($post_id, '_yoast_wpseo_title', $seo_title);
            }
            
            // Сохраняем мета-описание
            if (!empty($meta_description)) {
                $result['description_saved'] = update_post_meta($post_id, '_yoast_wpseo_metadesc', $meta_description);
            }
            
            // Сохраняем фокусное ключевое слово
            if (!empty($focus_keyword)) {
                $result['keyword_saved'] = update_post_meta($post_id, '_yoast_wpseo_focuskw', $focus_keyword);
            }
            
            // Обновляем timestamp последнего обновления
            update_post_meta($post_id, '_ai_assistant_last_updated', current_time('timestamp'));
            
            // Обновляем статус оптимизации
            update_post_meta($post_id, '_ai_assistant_optimized', 'manual');
            
            // Сохраняем информацию о генерации
            $generation_meta = array(
                'generated_at' => current_time('mysql'),
                'saved_at' => current_time('mysql'),
                'generated_by' => get_current_user_id(),
                'ai_model' => 'gpt-4.1-2025-04-14',
                'plugin_version' => AI_ASSISTANT_VERSION,
                'save_method' => 'manual_modal'
            );
            update_post_meta($post_id, '_ai_assistant_generation_info', $generation_meta);
            
            // Логируем действие
            $this->log_debug("SEO data manually saved for post {$post_id}", array(
                'seo_title' => $seo_title,
                'meta_description' => substr($meta_description, 0, 50) . '...',
                'focus_keyword' => $focus_keyword,
                'user_id' => get_current_user_id()
            ));
            
            // Применяем хук после сохранения
            do_action('ai_assistant_after_save_seo_data', $post_id, $seo_data, $result);
            
            // Возвращаем успешный результат
            wp_send_json_success(array(
                'message' => __('SEO-данные успешно сохранены', 'ai-assistant'),
                'post_id' => $post_id,
                'saved_fields' => $result,
                'timestamp' => current_time('Y-m-d H:i:s')
            ));
            
        } catch (Exception $e) {
            // Логируем ошибку
            $this->log_error("Error saving SEO data for post {$post_id}: " . $e->getMessage());
            
            wp_send_json_error(__('Ошибка при сохранении данных: ', 'ai-assistant') . $e->getMessage());
        }
    }
    
    /**
     * Логирование отладочной информации
     */
    private function log_debug($message, $data = null) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $log_message = 'AI Assistant DEBUG: ' . $message;
            if ($data) {
                $log_message .= ' | Data: ' . json_encode($data);
            }
            error_log($log_message);
        }
    }
    
    /**
     * Логирование ошибок
     */
    private function log_error($message, $data = null) {
        $log_message = 'AI Assistant ERROR: ' . $message;
        if ($data) {
            $log_message .= ' | Data: ' . json_encode($data);
        }
        error_log($log_message);
    }
    
    /**
     * Деактивация плагина
     */
    public function deactivate() {
        // Очистка при деактивации (если необходимо)
        // В данном случае оставляем настройки
    }
}

/**
 * Функция для получения экземпляра плагина
 */
function ai_assistant() {
    return AI_Assistant::get_instance();
}

// Запускаем плагин
ai_assistant();
