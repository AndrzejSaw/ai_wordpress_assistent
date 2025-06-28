<?php
/**
 * Тест функциональности получения тегов и категорий
 * Проверяет работу универсальной системы таксономий
 */

// Предотвращаем прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тест поддержки тегов и категорий</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-section {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin: 20px 0;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .post-type-header {
            background: #0073aa;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .taxonomy-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 10px;
            margin: 10px 0;
        }
        .taxonomy-name {
            font-weight: bold;
            color: #495057;
        }
        .taxonomy-terms {
            color: #6c757d;
            margin-top: 5px;
        }
        .no-taxonomies {
            color: #6c757d;
            font-style: italic;
        }
        .supported-taxonomies {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .code-example {
            background: #f8f8f8;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            font-family: monospace;
            margin: 10px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🏷️ Тест поддержки тегов и категорий в AI Assistant</h1>
    
    <div class="supported-taxonomies">
        <h2>✅ Поддерживаемые таксономии</h2>
        <p>AI Assistant теперь автоматически определяет и использует ВСЕ таксономии для каждого типа записи:</p>
        <ul>
            <li><strong>Стандартные записи (post):</strong> Категории, Теги</li>
            <li><strong>Страницы (page):</strong> Любые кастомные таксономии</li>
            <li><strong>WooCommerce (product):</strong> Категории товаров, Теги товаров, Атрибуты</li>
            <li><strong>Вакансии (vacancy):</strong> Категории вакансий, Тип работы, Местоположение</li>
            <li><strong>Работодатели (employer):</strong> Любые кастомные таксономии</li>
            <li><strong>Любые кастомные типы записей</strong> с их таксономиями</li>
        </ul>
    </div>

    <?php
    // Функция для имитации получения таксономий (копия из плагина)
    function test_get_taxonomies_context($post_type) {
        // Получаем все таксономии для данного типа записи
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        
        $result = array();
        
        if (empty($taxonomies)) {
            return array('message' => 'Нет зарегистрированных таксономий для этого типа записи');
        }
        
        $excluded_taxonomies = array('nav_menu', 'link_category', 'post_format');
        
        foreach ($taxonomies as $taxonomy_slug => $taxonomy_object) {
            // Пропускаем исключенные таксономии
            if (in_array($taxonomy_slug, $excluded_taxonomies)) {
                continue;
            }
            
            $result[] = array(
                'slug' => $taxonomy_slug,
                'name' => $taxonomy_object->label,
                'public' => $taxonomy_object->public,
                'hierarchical' => $taxonomy_object->hierarchical,
                'show_ui' => $taxonomy_object->show_ui
            );
        }
        
        return $result;
    }

    // Тестируемые типы записей
    $post_types_to_test = array(
        'post' => 'Записи блога',
        'page' => 'Страницы',
        'product' => 'Товары (WooCommerce)',
        'vacancy' => 'Вакансии',
        'employer' => 'Работодатели',
        'event' => 'События',
        'portfolio' => 'Портфолио',
        'service' => 'Услуги'
    );

    foreach ($post_types_to_test as $post_type => $post_type_name) {
        $taxonomies = test_get_taxonomies_context($post_type);
        ?>
        <div class="test-section">
            <div class="post-type-header">
                <h3><?php echo esc_html($post_type_name); ?> (<?php echo esc_html($post_type); ?>)</h3>
            </div>
            
            <?php if (isset($taxonomies['message'])): ?>
                <div class="no-taxonomies">
                    <?php echo esc_html($taxonomies['message']); ?>
                </div>
            <?php else: ?>
                <p><strong>Найдено таксономий:</strong> <?php echo count($taxonomies); ?></p>
                
                <?php foreach ($taxonomies as $taxonomy): ?>
                    <div class="taxonomy-item">
                        <div class="taxonomy-name">
                            <?php echo esc_html($taxonomy['name']); ?> 
                            <code>(<?php echo esc_html($taxonomy['slug']); ?>)</code>
                        </div>
                        <div class="taxonomy-terms">
                            Публичная: <?php echo $taxonomy['public'] ? 'Да' : 'Нет'; ?> | 
                            Иерархическая: <?php echo $taxonomy['hierarchical'] ? 'Да' : 'Нет'; ?> | 
                            Показывается в UI: <?php echo $taxonomy['show_ui'] ? 'Да' : 'Нет'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    ?>

    <div class="test-section">
        <h2>🛠️ Примеры использования</h2>
        
        <h3>1. Исключение таксономий из генерации SEO</h3>
        <div class="code-example">
// В functions.php темы или в плагине
add_filter('ai_assistant_excluded_taxonomies', function($excluded, $post_type, $post_id) {
    // Исключаем служебные таксономии WooCommerce
    if ($post_type === 'product') {
        $excluded[] = 'product_visibility';
        $excluded[] = 'product_shipping_class';
    }
    
    return $excluded;
}, 10, 3);
        </div>
        
        <h3>2. Кастомные названия таксономий</h3>
        <div class="code-example">
// Добавление кастомных переводов названий таксономий
add_filter('ai_assistant_taxonomy_translations', function($translations, $taxonomy_slug, $taxonomy_object) {
    $translations['my_custom_taxonomy'] = 'Моя кастомная таксономия';
    $translations['another_taxonomy'] = 'Другая таксономия';
    
    return $translations;
}, 10, 3);
        </div>
        
        <h3>3. Модификация контекста таксономий</h3>
        <div class="code-example">
// Добавление дополнительной информации о таксономиях
add_filter('ai_assistant_taxonomies_context', function($context, $post_id, $post_type) {
    // Добавляем количество терминов для записей блога
    if ($post_type === 'post') {
        $total_terms = wp_count_terms(array('taxonomy' => array('category', 'post_tag')));
        $context['Общая информация'] = "Всего терминов в блоге: {$total_terms}";
    }
    
    return $context;
}, 10, 3);
        </div>
        
        <h3>4. Изменение отображаемого названия таксономии</h3>
        <div class="code-example">
// Кастомизация названий таксономий
add_filter('ai_assistant_taxonomy_display_name', function($display_name, $taxonomy_slug, $taxonomy_object, $post_type) {
    // Для товаров показываем более подробные названия
    if ($post_type === 'product' && $taxonomy_slug === 'pa_color') {
        return 'Цветовая гамма товара';
    }
    
    return $display_name;
}, 10, 4);
        </div>
    </div>

    <div class="test-section">
        <h2>🎯 Как это влияет на SEO-генерацию</h2>
        
        <p><strong>Теперь AI Assistant использует контекст тегов и категорий для:</strong></p>
        <ul>
            <li>🎯 <strong>Генерации более релевантных ключевых слов</strong> на основе категорий</li>
            <li>📝 <strong>Создания точных SEO заголовков</strong> с учетом тематики (тегов)</li>
            <li>📄 <strong>Написания мета-описаний</strong> с упоминанием категории</li>
            <li>🔍 <strong>Улучшения контекстности</strong> для разных типов контента</li>
        </ul>
        
        <h3>Пример контекста для записи блога:</h3>
        <div class="code-example">
Заголовок: "Как настроить WordPress"
Категории: "Туториалы, WordPress, Веб-разработка"
Теги: "настройка, CMS, сайт, хостинг"
Контент: "В этой статье мы рассмотрим..."

AI сгенерирует:
• Ключевое слово: "настройка WordPress"
• SEO заголовок: "Как настроить WordPress: пошаговый гайд"
• Мета-описание: "Полное руководство по настройке WordPress. Узнайте, как правильно установить и настроить CMS для вашего сайта."
        </div>
        
        <h3>Пример контекста для товара WooCommerce:</h3>
        <div class="code-example">
Заголовок: "Красные кроссовки Nike"
Категории товаров: "Обувь, Спорт"
Теги товаров: "кроссовки, бег, Nike"
Цвет: "Красный"
Размер: "42, 43, 44"

AI сгенерирует:
• Ключевое слово: "красные кроссовки Nike"
• SEO заголовок: "Красные кроссовки Nike - спортивная обувь"
• Мета-описание: "Купите красные кроссовки Nike для бега и спорта. Размеры 42-44. Качественная спортивная обувь с доставкой."
        </div>
    </div>

    <div style="background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; padding: 15px; margin: 20px 0;">
        <h3>🚀 Результат</h3>
        <p><strong>AI Assistant теперь ПОЛНОСТЬЮ поддерживает теги и категории:</strong></p>
        <ul>
            <li>✅ <strong>Автоматическое обнаружение</strong> всех таксономий любого типа записи</li>
            <li>✅ <strong>Универсальная система</strong> - работает с любыми плагинами</li>
            <li>✅ <strong>Расширяемость</strong> через фильтры и хуки</li>
            <li>✅ <strong>Логирование</strong> для отладки и мониторинга</li>
            <li>✅ <strong>Поддержка WooCommerce</strong>, Job Manager и других плагинов</li>
        </ul>
    </div>

</body>
</html>
